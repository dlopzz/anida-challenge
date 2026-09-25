<?php

declare(strict_types=1);

namespace App\Dominio\Puertos;

use App\Dominio\Cuit;
use App\Dominio\Excepciones\BureauNoDisponible;
use App\Dominio\InformeBureau;

interface Bureau
{
    /** @throws BureauNoDisponible si el proveedor falla o no responde a tiempo */
    public function consultar(Cuit $cuit): InformeBureau;
}
