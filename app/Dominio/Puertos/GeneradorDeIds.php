<?php

declare(strict_types=1);

namespace App\Dominio\Puertos;

interface GeneradorDeIds
{
    public function nuevo(): string;
}
