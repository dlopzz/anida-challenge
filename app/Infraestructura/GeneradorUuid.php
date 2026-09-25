<?php

declare(strict_types=1);

namespace App\Infraestructura;

use App\Dominio\Puertos\GeneradorDeIds;
use Illuminate\Support\Str;

final class GeneradorUuid implements GeneradorDeIds
{
    public function nuevo(): string
    {
        return (string) Str::uuid();
    }
}
