<?php

declare(strict_types=1);

namespace Tests\Unit\Dominio;

use App\Dominio\Dinero;
use PHPUnit\Framework\TestCase;

final class DineroTest extends TestCase
{
    public function test_convierte_pesos_a_centavos(): void
    {
        $this->assertSame(90_000_000, Dinero::desdePesos(900_000)->centavos());
    }

    public function test_rechaza_negativos(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dinero::desdeCentavos(-1);
    }

    public function test_supera_porcentaje_es_estricto(): void
    {
        $ingreso = Dinero::desdePesos(900_000);

        // Exactamente el 30 %: no supera.
        $this->assertFalse(Dinero::desdePesos(270_000)->superaPorcentajeDe($ingreso, 30));

        // Un centavo más: supera.
        $this->assertTrue(Dinero::desdeCentavos(27_000_001)->superaPorcentajeDe($ingreso, 30));
    }
}
