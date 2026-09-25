<?php

declare(strict_types=1);

namespace Tests\Unit\Dominio;

use App\Dominio\Cuit;
use App\Dominio\Excepciones\CuitInvalido;
use PHPUnit\Framework\TestCase;

final class CuitTest extends TestCase
{
    public function test_acepta_el_cuit_del_enunciado_con_guiones(): void
    {
        $cuit = Cuit::desde('20-12345678-6');

        $this->assertSame('20123456786', $cuit->valor());
    }

    public function test_acepta_el_mismo_cuit_sin_guiones(): void
    {
        $this->assertTrue(Cuit::esValido('20123456786'));
    }

    public function test_rechaza_verificador_incorrecto(): void
    {
        $this->assertFalse(Cuit::esValido('20-12345678-5'));
    }

    public function test_rechaza_menos_de_once_digitos(): void
    {
        $this->assertFalse(Cuit::esValido('20-1234567-6'));
    }

    public function test_rechaza_letras(): void
    {
        $this->assertFalse(Cuit::esValido('20-1234567A-6'));
    }

    public function test_resto_cero_da_verificador_cero(): void
    {
        // 20-00000006-?: suma 22, 22 % 11 = 0 -> el verificador es 0.
        $this->assertTrue(Cuit::esValido('20-00000006-0'));
    }

    public function test_resto_uno_no_tiene_verificador_valido(): void
    {
        // 20-00000001-?: 12 % 11 = 1 -> 11 - 1 = 10, y un dígito no puede ser 10.
        foreach (range(0, 9) as $dv) {
            $this->assertFalse(Cuit::esValido("20-00000001-{$dv}"), "dv {$dv} no debería ser válido");
        }
    }

    public function test_desde_lanza_excepcion_si_es_invalido(): void
    {
        $this->expectException(CuitInvalido::class);

        Cuit::desde('20-12345678-5');
    }
}
