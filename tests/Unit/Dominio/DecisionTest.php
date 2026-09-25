<?php

declare(strict_types=1);

namespace Tests\Unit\Dominio;

use App\Dominio\Decision;
use App\Dominio\Estado;
use App\Dominio\Motivo;
use PHPUnit\Framework\TestCase;

final class DecisionTest extends TestCase
{
    public function test_aprobada_sin_bureau_pasa_a_revision_manual(): void
    {
        $decision = Decision::aprobada()->sinBureau();

        $this->assertSame(Estado::REVISION_MANUAL, $decision->estado);
        $this->assertSame([Motivo::BUREAU_NO_DISPONIBLE], $decision->motivos);
    }

    public function test_revision_manual_sin_bureau_conserva_sus_motivos(): void
    {
        $decision = Decision::revisionManual(Motivo::MONTO_ALTO)->sinBureau();

        $this->assertSame(Estado::REVISION_MANUAL, $decision->estado);
        $this->assertSame([Motivo::MONTO_ALTO, Motivo::BUREAU_NO_DISPONIBLE], $decision->motivos);
    }

    public function test_un_rechazo_no_cambia_sin_bureau(): void
    {
        $decision = Decision::rechazada(Motivo::ANTIGUEDAD_INSUFICIENTE)->sinBureau();

        $this->assertSame(Estado::RECHAZADA, $decision->estado);
        $this->assertSame([Motivo::ANTIGUEDAD_INSUFICIENTE], $decision->motivos);
    }
}
