<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Dominio\Cuit;
use App\Dominio\Excepciones\BureauNoDisponible;
use App\Dominio\InformeBureau;
use App\Dominio\Puertos\Bureau;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class SolicitudesTest extends TestCase
{
    use RefreshDatabase;

    /** El JSON del enunciado. */
    private const EJEMPLO = [
        'cuit' => '20-12345678-6',
        'ingreso_mensual' => 900000,
        'antiguedad_laboral_meses' => 18,
        'monto_solicitado' => 1000000,
        'cuotas' => 12,
        'tna' => 60,
    ];

    public function test_aprueba_el_ejemplo_del_enunciado(): void
    {
        $respuesta = $this->solicitar(self::EJEMPLO, 'key-1');

        $respuesta->assertStatus(201)
            ->assertJson([
                'decision' => 'aprobada',
                'cuota' => 11282541,
                'motivos' => [],
            ]);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $respuesta->json('id'));
    }

    public function test_guarda_la_solicitud_con_decision_motivos_y_version_de_reglas(): void
    {
        config(['credito.reglas_version' => 'v-test']);

        $respuesta = $this->solicitar(self::EJEMPLO, 'key-1');

        $this->assertDatabaseHas('solicitudes', [
            'id' => $respuesta->json('id'),
            'cuit' => '20123456786',
            'ingreso_mensual' => 90_000_000,   // centavos
            'monto_solicitado' => 100_000_000, // centavos
            'cuota' => 11_282_541,
            'decision' => 'aprobada',
            'reglas_version' => 'v-test',
        ]);
    }

    public function test_rechaza_cuando_la_cuota_supera_el_porcentaje_del_ingreso(): void
    {
        $respuesta = $this->solicitar(['ingreso_mensual' => 300000] + self::EJEMPLO, 'key-1');

        $respuesta->assertStatus(201)
            ->assertJson([
                'decision' => 'rechazada',
                'motivos' => ['RELACION_CUOTA_INGRESO_EXCEDIDA'],
            ]);
    }

    public function test_monto_alto_pasa_a_revision_manual(): void
    {
        $respuesta = $this->solicitar([
            'ingreso_mensual' => 90000000,
            'monto_solicitado' => 5000001,
        ] + self::EJEMPLO, 'key-1');

        $respuesta->assertStatus(201)
            ->assertJson([
                'decision' => 'revision_manual',
                'motivos' => ['MONTO_ALTO'],
            ]);
    }

    public function test_los_umbrales_salen_de_la_configuracion(): void
    {
        config(['credito.relacion_cuota_ingreso_max' => 10]);

        // 12,5 % del ingreso: aprobado con el 30 %, rechazado con el 10 %.
        $this->solicitar(self::EJEMPLO, 'key-1')
            ->assertJson(['decision' => 'rechazada']);
    }

    public function test_cuit_invalido_responde_422(): void
    {
        $this->solicitar(['cuit' => '20-12345678-5'] + self::EJEMPLO, 'key-1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cuit']);

        $this->assertDatabaseCount('solicitudes', 0);
    }

    public function test_cuerpo_incompleto_responde_422(): void
    {
        $this->solicitar(['cuit' => '20-12345678-6'], 'key-1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ingreso_mensual', 'monto_solicitado', 'cuotas', 'tna']);
    }

    public function test_sin_idempotency_key_responde_400(): void
    {
        $this->postJson('/solicitudes', self::EJEMPLO)
            ->assertStatus(400);

        $this->assertDatabaseCount('solicitudes', 0);
    }

    public function test_reintento_con_la_misma_clave_devuelve_lo_mismo_sin_duplicar(): void
    {
        $primera = $this->solicitar(self::EJEMPLO, 'key-1');
        $segunda = $this->solicitar(self::EJEMPLO, 'key-1');

        $primera->assertStatus(201);
        $segunda->assertStatus(200);
        $this->assertSame($primera->json(), $segunda->json());
        $this->assertDatabaseCount('solicitudes', 1);
    }

    public function test_el_mismo_cuerpo_con_otro_orden_de_claves_es_el_mismo_pedido(): void
    {
        $primera = $this->solicitar(self::EJEMPLO, 'key-1');
        $segunda = $this->solicitar(array_reverse(self::EJEMPLO, preserve_keys: true), 'key-1');

        $segunda->assertStatus(200);
        $this->assertSame($primera->json('id'), $segunda->json('id'));
    }

    public function test_la_misma_clave_con_otro_cuerpo_responde_409(): void
    {
        $this->solicitar(self::EJEMPLO, 'key-1')->assertStatus(201);

        $this->solicitar(['ingreso_mensual' => 300000] + self::EJEMPLO, 'key-1')
            ->assertStatus(409);

        $this->assertDatabaseCount('solicitudes', 1);
    }

    public function test_claves_distintas_son_solicitudes_distintas(): void
    {
        $primera = $this->solicitar(self::EJEMPLO, 'key-1');
        $segunda = $this->solicitar(self::EJEMPLO, 'key-2');

        $this->assertNotSame($primera->json('id'), $segunda->json('id'));
        $this->assertDatabaseCount('solicitudes', 2);
    }

    public function test_si_el_bureau_falla_queda_en_revision_manual_y_se_guarda(): void
    {
        $this->conBureauCaido();

        $respuesta = $this->solicitar(self::EJEMPLO, 'key-1');

        $respuesta->assertStatus(201)
            ->assertJson([
                'decision' => 'revision_manual',
                'motivos' => ['BUREAU_NO_DISPONIBLE'],
            ]);

        $this->assertDatabaseHas('solicitudes', [
            'id' => $respuesta->json('id'),
            'decision' => 'revision_manual',
        ]);
    }

    public function test_un_rechazo_no_consulta_al_bureau(): void
    {
        $this->conBureauCaido();

        $this->solicitar(['ingreso_mensual' => 300000] + self::EJEMPLO, 'key-1')
            ->assertJson([
                'decision' => 'rechazada',
                'motivos' => ['RELACION_CUOTA_INGRESO_EXCEDIDA'],
            ]);
    }

    private function conBureauCaido(): void
    {
        $this->app->instance(Bureau::class, new class implements Bureau {
            public function consultar(Cuit $cuit): InformeBureau
            {
                throw new BureauNoDisponible('simulado');
            }
        });
    }

    /** @param array<string, mixed> $cuerpo */
    private function solicitar(array $cuerpo, string $idempotencyKey): TestResponse
    {
        return $this->postJson('/solicitudes', $cuerpo, ['Idempotency-Key' => $idempotencyKey]);
    }
}
