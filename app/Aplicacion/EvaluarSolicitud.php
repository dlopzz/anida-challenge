<?php

declare(strict_types=1);

namespace App\Aplicacion;

use App\Aplicacion\Excepciones\SolicitudDuplicadaConOtroCuerpo;
use App\Dominio\CalculadoraCuota;
use App\Dominio\Cuit;
use App\Dominio\Dinero;
use App\Dominio\Estado;
use App\Dominio\Excepciones\BureauNoDisponible;
use App\Dominio\Excepciones\ClaveIdempotenciaDuplicada;
use App\Dominio\PoliticaDeCredito;
use App\Dominio\Puertos\Bureau;
use App\Dominio\Puertos\GeneradorDeIds;
use App\Dominio\Puertos\SolicitudRepositorio;
use App\Dominio\Solicitud;

/**
 * Caso de uso. Coordina: idempotencia → cuota → política → bureau → guardar.
 * No tiene reglas de negocio propias: eso vive en el dominio.
 */
final class EvaluarSolicitud
{
    public function __construct(
        private readonly SolicitudRepositorio $solicitudes,
        private readonly GeneradorDeIds $ids,
        private readonly CalculadoraCuota $calculadora,
        private readonly PoliticaDeCredito $politica,
        private readonly Bureau $bureau,
    ) {
    }

    public function ejecutar(EvaluarSolicitudComando $comando): ResultadoEvaluacion
    {
        $cuerpoHash = $this->hashDelCuerpo($comando);

        $existente = $this->solicitudes->porIdempotencyKey($comando->idempotencyKey);
        if ($existente !== null) {
            return $this->responderReintento($existente, $cuerpoHash, $comando->idempotencyKey);
        }

        $monto = Dinero::desdePesos($comando->montoSolicitadoPesos);
        $ingreso = Dinero::desdePesos($comando->ingresoMensualPesos);

        $cuit = Cuit::desde($comando->cuit);

        $cuota = $this->calculadora->calcular($monto, $comando->tna, $comando->cuotas);
        $decision = $this->politica->evaluar($cuota, $ingreso, $comando->antiguedadLaboralMeses, $monto);

        // Un rechazo no necesita al bureau. Si el bureau falla, la solicitud no se pierde:
        // queda en revisión manual con el motivo, y se guarda igual.
        if ($decision->estado !== Estado::RECHAZADA) {
            try {
                $this->bureau->consultar($cuit);
            } catch (BureauNoDisponible) {
                $decision = $decision->sinBureau();
            }
        }

        $solicitud = new Solicitud(
            id: $this->ids->nuevo(),
            idempotencyKey: $comando->idempotencyKey,
            cuerpoHash: $cuerpoHash,
            cuit: $cuit,
            ingresoMensual: $ingreso,
            antiguedadLaboralMeses: $comando->antiguedadLaboralMeses,
            montoSolicitado: $monto,
            cuotas: $comando->cuotas,
            tna: $comando->tna,
            cuota: $cuota,
            decision: $decision,
            reglasVersion: $this->politica->version(),
        );

        try {
            $this->solicitudes->guardar($solicitud);
        } catch (ClaveIdempotenciaDuplicada) {
            // Dos requests iguales llegaron al mismo tiempo: la base dejó pasar una sola.
            // Respondemos con la que ganó, como si fuera un reintento.
            $ganadora = $this->solicitudes->porIdempotencyKey($comando->idempotencyKey);

            return $this->responderReintento($ganadora, $cuerpoHash, $comando->idempotencyKey);
        }

        return ResultadoEvaluacion::desde($solicitud, repetida: false);
    }

    private function responderReintento(Solicitud $existente, string $cuerpoHash, string $clave): ResultadoEvaluacion
    {
        if (! $existente->coincideCon($cuerpoHash)) {
            throw SolicitudDuplicadaConOtroCuerpo::paraClave($clave);
        }

        return ResultadoEvaluacion::desde($existente, repetida: true);
    }

    /**
     * Hash canónico del cuerpo: se arma desde los valores ya normalizados y con las claves
     * en un orden fijo, así el mismo pedido da el mismo hash aunque cambie el formato del JSON.
     */
    private function hashDelCuerpo(EvaluarSolicitudComando $comando): string
    {
        return hash('sha256', json_encode([
            'cuit' => Cuit::desde($comando->cuit)->valor(),
            'ingreso_mensual' => $comando->ingresoMensualPesos,
            'antiguedad_laboral_meses' => $comando->antiguedadLaboralMeses,
            'monto_solicitado' => $comando->montoSolicitadoPesos,
            'cuotas' => $comando->cuotas,
            'tna' => sprintf('%.4F', $comando->tna),
        ], JSON_THROW_ON_ERROR));
    }
}
