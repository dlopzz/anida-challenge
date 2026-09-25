<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Sistema francés: C = P · i / (1 − (1 + i)^−n), con i = TNA / 12.
 * El único float vive dentro del cálculo; se redondea al centavo una sola vez, al final.
 */
final class CalculadoraCuota
{
    public function calcular(Dinero $capital, float $tna, int $cuotas): Dinero
    {
        if ($cuotas < 1) {
            throw new \InvalidArgumentException('La cantidad de cuotas debe ser al menos 1.');
        }
        if ($tna < 0) {
            throw new \InvalidArgumentException('La TNA no puede ser negativa.');
        }

        $capitalCentavos = $capital->centavos();
        $i = $tna / 100 / 12;

        // Con tasa cero la fórmula divide por cero: la cuota es el capital en partes iguales.
        if ($i === 0.0) {
            return Dinero::desdeCentavos((int) round($capitalCentavos / $cuotas));
        }

        $cuota = $capitalCentavos * $i / (1 - (1 + $i) ** -$cuotas);

        return Dinero::desdeCentavos((int) round($cuota));
    }
}
