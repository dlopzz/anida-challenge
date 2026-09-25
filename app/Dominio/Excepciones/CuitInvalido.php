<?php

declare(strict_types=1);

namespace App\Dominio\Excepciones;

final class CuitInvalido extends \DomainException
{
    public static function porValor(string $valor): self
    {
        return new self("CUIT inválido: {$valor}");
    }
}
