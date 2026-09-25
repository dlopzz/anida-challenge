<?php

declare(strict_types=1);

namespace App\Dominio;

enum Estado: string
{
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
    case REVISION_MANUAL = 'revision_manual';
}
