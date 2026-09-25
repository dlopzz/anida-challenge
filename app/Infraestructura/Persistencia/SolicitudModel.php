<?php

declare(strict_types=1);

namespace App\Infraestructura\Persistencia;

use Illuminate\Database\Eloquent\Model;

/** Modelo Eloquent. Es un detalle de persistencia: el dominio no lo conoce. */
final class SolicitudModel extends Model
{
    protected $table = 'solicitudes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'motivos' => 'array',
    ];
}
