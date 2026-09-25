<?php

declare(strict_types=1);

namespace App\Dominio\Excepciones;

/** Dos solicitudes intentaron guardarse con la misma Idempotency-Key (carrera). */
final class ClaveIdempotenciaDuplicada extends \RuntimeException
{
}
