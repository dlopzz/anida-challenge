<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Lo que devuelve el bureau de crédito. Hoy ninguna regla lo usa: el enunciado sólo pide
 * que, si el bureau falla, la solicitud pase a revisión manual sin perderse.
 */
final class InformeBureau
{
    public function __construct(public readonly int $situacion)
    {
    }
}
