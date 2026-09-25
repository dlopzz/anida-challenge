<?php

declare(strict_types=1);

namespace Tests\Unit\Dominio;

use App\Dominio\Decision;
use App\Dominio\Dinero;
use App\Dominio\Estado;
use App\Dominio\Motivo;
use App\Dominio\PoliticaDeCredito;
use App\Dominio\Umbrales;
use PHPUnit\Framework\TestCase;

final class PoliticaDeCreditoTest extends TestCase
{
    private PoliticaDeCredito $politica;

    protected function setUp(): void
    {
        $this->politica = new PoliticaDeCredito(new Umbrales(
            relacionCuotaIngresoMax: 30,
            antiguedadMinimaMeses: 6,
            montoRevisionManual: Dinero::desdePesos(5_000_000),
            version: 'test-1',
        ));
    }

    /** Ejemplo de control del enunciado: cuota 112.825,41 sobre ingreso 900.000 -> 12,5 %. */
    public function test_aprueba_el_ejemplo_del_enunciado(): void
    {
        $decision = $this->evaluar(cuota: 11_282_541, ingreso: 900_000, antiguedad: 18, monto: 1_000_000);

        $this->assertSame(Estado::APROBADA, $decision->estado);
        $this->assertSame([], $decision->motivos);
    }

    /** Segundo ejemplo de control: ingreso 300.000 -> 37,6 %. */
    public function test_rechaza_si_la_cuota_supera_el_porcentaje_del_ingreso(): void
    {
        $decision = $this->evaluar(cuota: 11_282_541, ingreso: 300_000, antiguedad: 18, monto: 1_000_000);

        $this->assertSame(Estado::RECHAZADA, $decision->estado);
        $this->assertSame([Motivo::RELACION_CUOTA_INGRESO_EXCEDIDA], $decision->motivos);
    }

    public function test_exactamente_el_porcentaje_maximo_no_es_rechazo(): void
    {
        // 30 % de 900.000 = 270.000.
        $decision = $this->evaluar(cuota: 27_000_000, ingreso: 900_000, antiguedad: 18, monto: 1_000_000);

        $this->assertSame(Estado::APROBADA, $decision->estado);
    }

    public function test_rechaza_antiguedad_menor_a_la_minima(): void
    {
        $decision = $this->evaluar(cuota: 11_282_541, ingreso: 900_000, antiguedad: 5, monto: 1_000_000);

        $this->assertSame(Estado::RECHAZADA, $decision->estado);
        $this->assertSame([Motivo::ANTIGUEDAD_INSUFICIENTE], $decision->motivos);
    }

    public function test_antiguedad_igual_a_la_minima_alcanza(): void
    {
        $decision = $this->evaluar(cuota: 11_282_541, ingreso: 900_000, antiguedad: 6, monto: 1_000_000);

        $this->assertSame(Estado::APROBADA, $decision->estado);
    }

    public function test_acumula_todos_los_motivos_de_rechazo(): void
    {
        $decision = $this->evaluar(cuota: 11_282_541, ingreso: 300_000, antiguedad: 5, monto: 1_000_000);

        $this->assertSame(Estado::RECHAZADA, $decision->estado);
        $this->assertSame(
            [Motivo::RELACION_CUOTA_INGRESO_EXCEDIDA, Motivo::ANTIGUEDAD_INSUFICIENTE],
            $decision->motivos,
        );
    }

    public function test_monto_alto_sin_rechazos_pasa_a_revision_manual(): void
    {
        $decision = $this->evaluar(cuota: 1_000, ingreso: 900_000, antiguedad: 18, monto: 5_000_001);

        $this->assertSame(Estado::REVISION_MANUAL, $decision->estado);
        $this->assertSame([Motivo::MONTO_ALTO], $decision->motivos);
    }

    public function test_monto_igual_al_umbral_no_es_alto(): void
    {
        $decision = $this->evaluar(cuota: 1_000, ingreso: 900_000, antiguedad: 18, monto: 5_000_000);

        $this->assertSame(Estado::APROBADA, $decision->estado);
    }

    public function test_el_rechazo_gana_sobre_la_revision_manual(): void
    {
        $decision = $this->evaluar(cuota: 1_000, ingreso: 900_000, antiguedad: 5, monto: 5_000_001);

        $this->assertSame(Estado::RECHAZADA, $decision->estado);
        $this->assertSame([Motivo::ANTIGUEDAD_INSUFICIENTE], $decision->motivos);
    }

    public function test_expone_la_version_de_las_reglas(): void
    {
        $this->assertSame('test-1', $this->politica->version());
    }

    private function evaluar(int $cuota, int $ingreso, int $antiguedad, int $monto): Decision
    {
        return $this->politica->evaluar(
            Dinero::desdeCentavos($cuota),
            Dinero::desdePesos($ingreso),
            $antiguedad,
            Dinero::desdePesos($monto),
        );
    }
}
