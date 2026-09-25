<?php

declare(strict_types=1);

namespace App\Infraestructura\Persistencia;

use App\Dominio\Cuit;
use App\Dominio\Decision;
use App\Dominio\Dinero;
use App\Dominio\Estado;
use App\Dominio\Excepciones\ClaveIdempotenciaDuplicada;
use App\Dominio\Motivo;
use App\Dominio\Puertos\SolicitudRepositorio;
use App\Dominio\Solicitud;
use Illuminate\Database\UniqueConstraintViolationException;

/** Adaptador: traduce entre la entidad del dominio y la tabla. */
final class SolicitudEloquentRepositorio implements SolicitudRepositorio
{
    public function porIdempotencyKey(string $idempotencyKey): ?Solicitud
    {
        $modelo = SolicitudModel::query()->where('idempotency_key', $idempotencyKey)->first();

        return $modelo === null ? null : $this->aEntidad($modelo);
    }

    public function guardar(Solicitud $solicitud): void
    {
        try {
            SolicitudModel::query()->create([
                'id' => $solicitud->id,
                'idempotency_key' => $solicitud->idempotencyKey,
                'cuerpo_hash' => $solicitud->cuerpoHash,
                'cuit' => $solicitud->cuit->valor(),
                'ingreso_mensual' => $solicitud->ingresoMensual->centavos(),
                'antiguedad_laboral_meses' => $solicitud->antiguedadLaboralMeses,
                'monto_solicitado' => $solicitud->montoSolicitado->centavos(),
                'cuotas' => $solicitud->cuotas,
                'tna' => $solicitud->tna,
                'cuota' => $solicitud->cuota->centavos(),
                'decision' => $solicitud->decision->estado->value,
                'motivos' => $solicitud->decision->motivosComoTexto(),
                'reglas_version' => $solicitud->reglasVersion,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new ClaveIdempotenciaDuplicada($solicitud->idempotencyKey, previous: $e);
        }
    }

    private function aEntidad(SolicitudModel $modelo): Solicitud
    {
        return new Solicitud(
            id: $modelo->id,
            idempotencyKey: $modelo->idempotency_key,
            cuerpoHash: $modelo->cuerpo_hash,
            cuit: Cuit::desde($modelo->cuit),
            ingresoMensual: Dinero::desdeCentavos((int) $modelo->ingreso_mensual),
            antiguedadLaboralMeses: (int) $modelo->antiguedad_laboral_meses,
            montoSolicitado: Dinero::desdeCentavos((int) $modelo->monto_solicitado),
            cuotas: (int) $modelo->cuotas,
            tna: (float) $modelo->tna,
            cuota: Dinero::desdeCentavos((int) $modelo->cuota),
            decision: Decision::reconstruir(
                Estado::from($modelo->decision),
                array_map(fn (string $m) => Motivo::from($m), $modelo->motivos ?? []),
            ),
            reglasVersion: $modelo->reglas_version,
        );
    }
}
