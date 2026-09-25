<?php

declare(strict_types=1);

namespace App\Infraestructura\Bureau;

use App\Dominio\Cuit;
use App\Dominio\Excepciones\BureauNoDisponible;
use App\Dominio\InformeBureau;
use App\Dominio\Puertos\Bureau;

/**
 * Simula un proveedor externo que puede tardar o fallar.
 * Con los valores por defecto (latencia 0, tasa de fallo 0) siempre responde.
 */
final class BureauSimulado implements Bureau
{
    public function __construct(
        private readonly int $latenciaMs,
        private readonly int $timeoutMs,
        private readonly float $tasaFallo,
    ) {
    }

    public function consultar(Cuit $cuit): InformeBureau
    {
        if ($this->latenciaMs > $this->timeoutMs) {
            throw new BureauNoDisponible("El bureau superó el timeout de {$this->timeoutMs} ms.");
        }

        if ($this->latenciaMs > 0) {
            usleep($this->latenciaMs * 1000);
        }

        if ($this->tasaFallo > 0 && mt_rand() / mt_getrandmax() < $this->tasaFallo) {
            throw new BureauNoDisponible('El bureau devolvió un error.');
        }

        return new InformeBureau(situacion: 1);
    }
}
