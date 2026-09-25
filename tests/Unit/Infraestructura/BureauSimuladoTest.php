<?php

declare(strict_types=1);

namespace Tests\Unit\Infraestructura;

use App\Dominio\Cuit;
use App\Dominio\Excepciones\BureauNoDisponible;
use App\Infraestructura\Bureau\BureauSimulado;
use PHPUnit\Framework\TestCase;

final class BureauSimuladoTest extends TestCase
{
    public function test_responde_cuando_no_falla_ni_tarda(): void
    {
        $bureau = new BureauSimulado(latenciaMs: 0, timeoutMs: 2000, tasaFallo: 0);

        $this->assertSame(1, $bureau->consultar(Cuit::desde('20-12345678-6'))->situacion);
    }

    public function test_falla_siempre_con_tasa_uno(): void
    {
        $bureau = new BureauSimulado(latenciaMs: 0, timeoutMs: 2000, tasaFallo: 1.0);

        $this->expectException(BureauNoDisponible::class);
        $bureau->consultar(Cuit::desde('20-12345678-6'));
    }

    public function test_falla_si_la_latencia_supera_el_timeout(): void
    {
        $bureau = new BureauSimulado(latenciaMs: 3000, timeoutMs: 2000, tasaFallo: 0);

        $this->expectException(BureauNoDisponible::class);
        $bureau->consultar(Cuit::desde('20-12345678-6'));
    }
}
