<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Idempotencia: la clave es única; el hash del cuerpo detecta reintentos con otro contenido.
            $table->string('idempotency_key')->unique();
            $table->string('cuerpo_hash', 64);

            // Datos de la solicitud. Los montos van en centavos enteros, nunca float.
            $table->string('cuit', 11);
            $table->unsignedBigInteger('ingreso_mensual');
            $table->unsignedInteger('antiguedad_laboral_meses');
            $table->unsignedBigInteger('monto_solicitado');
            $table->unsignedInteger('cuotas');
            $table->decimal('tna', 8, 4);

            // Resultado y trazabilidad: qué se decidió, por qué, y con qué reglas.
            $table->unsignedBigInteger('cuota');
            $table->string('decision', 20);
            $table->json('motivos');
            $table->string('reglas_version', 50);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
