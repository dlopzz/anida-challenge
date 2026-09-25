<?php

declare(strict_types=1);

namespace App\Dominio\Puertos;

use App\Dominio\Excepciones\ClaveIdempotenciaDuplicada;
use App\Dominio\Solicitud;

interface SolicitudRepositorio
{
    public function porIdempotencyKey(string $idempotencyKey): ?Solicitud;

    /** @throws ClaveIdempotenciaDuplicada si otra solicitud ya usó la misma clave */
    public function guardar(Solicitud $solicitud): void;
}
