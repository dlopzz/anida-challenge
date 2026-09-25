<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Una solicitud ya evaluada. Se guarda completa: los datos, la cuota, la decisión
 * con sus motivos y la versión de las reglas que decidieron (regla 8).
 */
final class Solicitud
{
    public function __construct(
        public readonly string $id,
        public readonly string $idempotencyKey,
        public readonly string $cuerpoHash,
        public readonly Cuit $cuit,
        public readonly Dinero $ingresoMensual,
        public readonly int $antiguedadLaboralMeses,
        public readonly Dinero $montoSolicitado,
        public readonly int $cuotas,
        public readonly float $tna,
        public readonly Dinero $cuota,
        public readonly Decision $decision,
        public readonly string $reglasVersion,
    ) {
    }

    /** ¿Este reintento trae el mismo cuerpo que la solicitud original? */
    public function coincideCon(string $cuerpoHash): bool
    {
        return hash_equals($this->cuerpoHash, $cuerpoHash);
    }
}
