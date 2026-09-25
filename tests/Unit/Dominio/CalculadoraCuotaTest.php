<?php

declare(strict_types=1);

namespace Tests\Unit\Dominio;

use App\Dominio\CalculadoraCuota;
use App\Dominio\Dinero;
use PHPUnit\Framework\TestCase;

final class CalculadoraCuotaTest extends TestCase
{
    private CalculadoraCuota $calculadora;

    protected function setUp(): void
    {
        $this->calculadora = new CalculadoraCuota();
    }

    public function test_ejemplo_del_enunciado(): void
    {
        // P = 1.000.000, TNA 60 %, n = 12.
        // La fórmula exacta da 112.825,41002...: redondeado al centavo, 11282541.
        // El enunciado muestra 11282540 (un centavo menos); ver README.
        $cuota = $this->calculadora->calcular(Dinero::desdePesos(1_000_000), 60, 12);

        $this->assertSame(11_282_541, $cuota->centavos());
    }

    public function test_una_sola_cuota_devuelve_capital_mas_un_mes_de_interes(): void
    {
        // 1.000.000 al 5 % mensual en una cuota: 1.050.000,00.
        $cuota = $this->calculadora->calcular(Dinero::desdePesos(1_000_000), 60, 1);

        $this->assertSame(105_000_000, $cuota->centavos());
    }

    public function test_tasa_cero_divide_el_capital_en_partes_iguales(): void
    {
        $cuota = $this->calculadora->calcular(Dinero::desdePesos(1_200), 0, 12);

        $this->assertSame(10_000, $cuota->centavos());
    }

    public function test_tasa_cero_redondea_al_centavo(): void
    {
        // 100 pesos en 3 cuotas: 3333,33... centavos -> 3333.
        $cuota = $this->calculadora->calcular(Dinero::desdePesos(100), 0, 3);

        $this->assertSame(3_333, $cuota->centavos());
    }

    public function test_rechaza_cero_cuotas(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculadora->calcular(Dinero::desdePesos(100), 60, 0);
    }

    public function test_rechaza_tna_negativa(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculadora->calcular(Dinero::desdePesos(100), -1, 12);
    }
}
