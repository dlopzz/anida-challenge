<?php

namespace App\Providers;

use App\Dominio\Dinero;
use App\Dominio\Puertos\Bureau;
use App\Dominio\Puertos\GeneradorDeIds;
use App\Dominio\Puertos\SolicitudRepositorio;
use App\Dominio\Umbrales;
use App\Infraestructura\Bureau\BureauSimulado;
use App\Infraestructura\GeneradorUuid;
use App\Infraestructura\Persistencia\SolicitudEloquentRepositorio;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Acá se enchufan los adaptadores a los puertos. Es el único lugar
     * donde el dominio y la infraestructura se conocen.
     */
    public function register(): void
    {
        $this->app->bind(SolicitudRepositorio::class, SolicitudEloquentRepositorio::class);
        $this->app->bind(GeneradorDeIds::class, GeneradorUuid::class);
        $this->app->bind(Bureau::class, fn () => new BureauSimulado(
            latenciaMs: (int) config('credito.bureau.latencia_ms'),
            timeoutMs: (int) config('credito.bureau.timeout_ms'),
            tasaFallo: (float) config('credito.bureau.tasa_fallo'),
        ));

        $this->app->singleton(Umbrales::class, fn () => new Umbrales(
            relacionCuotaIngresoMax: (int) config('credito.relacion_cuota_ingreso_max'),
            antiguedadMinimaMeses: (int) config('credito.antiguedad_minima_meses'),
            montoRevisionManual: Dinero::desdePesos((int) config('credito.monto_revision_manual')),
            version: (string) config('credito.reglas_version'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
