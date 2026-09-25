<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Aplicacion\EvaluarSolicitud;
use App\Aplicacion\EvaluarSolicitudComando;
use App\Aplicacion\Excepciones\SolicitudDuplicadaConOtroCuerpo;
use App\Http\Requests\EvaluarSolicitudRequest;
use Illuminate\Http\JsonResponse;

/** Adaptador de entrada: HTTP → caso de uso → HTTP. Sin lógica de negocio. */
final class SolicitudController extends Controller
{
    public function __invoke(EvaluarSolicitudRequest $request, EvaluarSolicitud $evaluar): JsonResponse
    {
        $idempotencyKey = (string) $request->header('Idempotency-Key', '');
        if ($idempotencyKey === '') {
            return response()->json(['error' => 'Falta la cabecera Idempotency-Key.'], 400);
        }

        $comando = new EvaluarSolicitudComando(
            idempotencyKey: $idempotencyKey,
            cuit: $request->string('cuit')->toString(),
            ingresoMensualPesos: $request->integer('ingreso_mensual'),
            antiguedadLaboralMeses: $request->integer('antiguedad_laboral_meses'),
            montoSolicitadoPesos: $request->integer('monto_solicitado'),
            cuotas: $request->integer('cuotas'),
            tna: $request->float('tna'),
        );

        try {
            $resultado = $evaluar->ejecutar($comando);
        } catch (SolicitudDuplicadaConOtroCuerpo $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        return response()->json($resultado->toArray(), $resultado->repetida ? 200 : 201);
    }
}
