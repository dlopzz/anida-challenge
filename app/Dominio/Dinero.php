<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Objeto de valor. Siempre en centavos enteros: nunca float.
 */
final class Dinero
{
    private function __construct(private readonly int $centavos)
    {
        if ($centavos < 0) {
            throw new \InvalidArgumentException('El dinero no puede ser negativo.');
        }
    }

    public static function desdePesos(int $pesos): self
    {
        return new self($pesos * 100);
    }

    public static function desdeCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    public function centavos(): int
    {
        return $this->centavos;
    }

    /**
     * ¿Este monto supera el porcentaje dado de otro?
     * Se compara en enteros: cuota * 100 > base * porcentaje.
     */
    public function superaPorcentajeDe(self $base, int $porcentaje): bool
    {
        return $this->centavos * 100 > $base->centavos * $porcentaje;
    }

    public function esMayorQue(self $otro): bool
    {
        return $this->centavos > $otro->centavos;
    }
}
