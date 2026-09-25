<?php

declare(strict_types=1);

namespace App\Aplicacion\Excepciones;

/** Misma Idempotency-Key con un cuerpo distinto: el cliente está reusando la clave (409). */
final class SolicitudDuplicadaConOtroCuerpo extends \RuntimeException
{
    public static function paraClave(string $idempotencyKey): self
    {
        return new self("La Idempotency-Key '{$idempotencyKey}' ya se usó con otro cuerpo.");
    }
}
