<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Dominio\Cuit;
use Illuminate\Foundation\Http\FormRequest;

/** Validación de forma. Si algo falla, Laravel responde 422 con el detalle. */
final class EvaluarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cuit' => ['required', 'string', function (string $atributo, mixed $valor, \Closure $falla) {
                if (! Cuit::esValido($valor)) {
                    $falla('El CUIT no es válido.');
                }
            }],
            'ingreso_mensual' => ['required', 'integer', 'min:0'],
            'antiguedad_laboral_meses' => ['required', 'integer', 'min:0'],
            'monto_solicitado' => ['required', 'integer', 'min:1'],
            'cuotas' => ['required', 'integer', 'min:1'],
            'tna' => ['required', 'numeric', 'min:0'],
        ];
    }
}
