<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Las reglas de negocio. No sabe de HTTP, de base de datos ni de configuración:
 * recibe los umbrales ya resueltos y devuelve una decisión con sus motivos.
 */
final class PoliticaDeCredito
{
    public function __construct(private readonly Umbrales $umbrales)
    {
    }

    public function evaluar(Dinero $cuota, Dinero $ingresoMensual, int $antiguedadMeses, Dinero $monto): Decision
    {
        $rechazos = [];

        if ($cuota->superaPorcentajeDe($ingresoMensual, $this->umbrales->relacionCuotaIngresoMax)) {
            $rechazos[] = Motivo::RELACION_CUOTA_INGRESO_EXCEDIDA;
        }

        if ($antiguedadMeses < $this->umbrales->antiguedadMinimaMeses) {
            $rechazos[] = Motivo::ANTIGUEDAD_INSUFICIENTE;
        }

        if ($rechazos !== []) {
            return Decision::rechazada(...$rechazos);
        }

        // Sólo se llega acá sin motivos de rechazo (regla 5).
        if ($monto->esMayorQue($this->umbrales->montoRevisionManual)) {
            return Decision::revisionManual(Motivo::MONTO_ALTO);
        }

        return Decision::aprobada();
    }

    public function version(): string
    {
        return $this->umbrales->version;
    }
}
