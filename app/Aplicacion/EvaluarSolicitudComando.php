<?php

declare(strict_types=1);

namespace App\Aplicacion;

/** Lo que entra por la API, ya validado en forma. */
final class EvaluarSolicitudComando
{
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly string $cuit,
        public readonly int $ingresoMensualPesos,
        public readonly int $antiguedadLaboralMeses,
        public readonly int $montoSolicitadoPesos,
        public readonly int $cuotas,
        public readonly float $tna,
    ) {
    }
}
