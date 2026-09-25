<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Los parámetros de la política de crédito. Vienen de configuración, nunca del código,
 * y llevan una versión para saber con qué reglas se decidió cada solicitud.
 */
final class Umbrales
{
    public function __construct(
        public readonly int $relacionCuotaIngresoMax,
        public readonly int $antiguedadMinimaMeses,
        public readonly Dinero $montoRevisionManual,
        public readonly string $version,
    ) {
        if ($relacionCuotaIngresoMax < 0 || $relacionCuotaIngresoMax > 100) {
            throw new \InvalidArgumentException('La relación cuota/ingreso máxima es un porcentaje entre 0 y 100.');
        }
        if ($antiguedadMinimaMeses < 0) {
            throw new \InvalidArgumentException('La antigüedad mínima no puede ser negativa.');
        }
        if ($version === '') {
            throw new \InvalidArgumentException('La versión de las reglas no puede estar vacía.');
        }
    }
}
