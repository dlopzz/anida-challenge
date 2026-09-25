<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Dominio\Excepciones\CuitInvalido;

/**
 * Objeto de valor. Un CUIT válido tiene 11 dígitos y un dígito verificador
 * que se calcula con módulo 11 sobre los diez primeros.
 */
final class Cuit
{
    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    private function __construct(private readonly string $digitos)
    {
    }

    public static function desde(string $valor): self
    {
        $digitos = self::normalizar($valor);

        if (! self::verificadorCorrecto($digitos)) {
            throw CuitInvalido::porValor($valor);
        }

        return new self($digitos);
    }

    public static function esValido(string $valor): bool
    {
        return self::verificadorCorrecto(self::normalizar($valor));
    }

    /** Los 11 dígitos, sin guiones. */
    public function valor(): string
    {
        return $this->digitos;
    }

    public function equals(self $otro): bool
    {
        return $this->digitos === $otro->digitos;
    }

    /** Acepta "20-12345678-6" o "20123456786": quita guiones y espacios. */
    private static function normalizar(string $valor): string
    {
        return str_replace(['-', ' '], '', trim($valor));
    }

    private static function verificadorCorrecto(string $digitos): bool
    {
        if (! preg_match('/^\d{11}$/', $digitos)) {
            return false;
        }

        $suma = 0;
        foreach (self::PESOS as $posicion => $peso) {
            $suma += (int) $digitos[$posicion] * $peso;
        }

        $resto = $suma % 11;
        $esperado = match ($resto) {
            0 => 0,
            1 => -1,        // 11 - 1 = 10: no existe CUIT con ese verificador
            default => 11 - $resto,
        };

        return $esperado === (int) $digitos[10];
    }
}
