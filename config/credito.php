<?php

/*
 * Umbrales de la política de crédito. Ninguno va fijo en el código:
 * se leen de .env y cada solicitud guarda la versión con la que se decidió.
 */
return [
    // Porcentaje máximo del ingreso mensual que puede ocupar la cuota.
    'relacion_cuota_ingreso_max' => (int) env('CREDITO_RELACION_MAX', 30),

    // Antigüedad laboral mínima, en meses.
    'antiguedad_minima_meses' => (int) env('CREDITO_ANTIGUEDAD_MIN', 6),

    // Monto (en pesos) a partir del cual la solicitud pasa a revisión manual.
    'monto_revision_manual' => (int) env('CREDITO_MONTO_REVISION', 5000000),

    // Identificador de la versión de reglas vigente.
    'reglas_version' => env('CREDITO_REGLAS_VERSION', '2026-09-25.1'),
];
