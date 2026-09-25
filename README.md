# Evaluación de una solicitud de crédito

API que recibe una solicitud de préstamo personal y responde si se aprueba, se rechaza o pasa a
revisión manual, siempre con los motivos. PHP 8.2, Laravel 12, SQLite.

## Cómo correrlo

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan serve            # http://127.0.0.1:8000
php artisan test             # 47 tests
```

Con Docker: `docker compose up --build` y queda en `http://127.0.0.1:8000`.

```bash
curl -X POST http://127.0.0.1:8000/solicitudes \
  -H 'Content-Type: application/json' -H 'Idempotency-Key: abc' \
  -d '{"cuit":"20-12345678-6","ingreso_mensual":900000,"antiguedad_laboral_meses":18,"monto_solicitado":1000000,"cuotas":12,"tna":60}'
```

| Caso | Código |
|---|---|
| Solicitud nueva | 201 |
| Misma `Idempotency-Key` con el mismo cuerpo | 200, misma respuesta, sin fila nueva |
| Misma `Idempotency-Key` con otro cuerpo | 409 |
| Sin `Idempotency-Key` | 400 |
| CUIT inválido o cuerpo incompleto | 422 |

## Cómo está organizado

Hexagonal dentro de Laravel. El núcleo no conoce el framework:

- `app/Dominio/` — las reglas. `Cuit`, `Dinero` (centavos enteros), `CalculadoraCuota`
  (sistema francés), `PoliticaDeCredito`, `Decision`, `Umbrales`, y los puertos
  (`SolicitudRepositorio`, `Bureau`, `GeneradorDeIds`). Se prueba sin base ni HTTP.
- `app/Aplicacion/EvaluarSolicitud.php` — el caso de uso: idempotencia → cuota → política →
  bureau → guardar. No tiene reglas propias.
- `app/Infraestructura/` — los adaptadores: repositorio Eloquent, bureau simulado, UUID.
- `app/Http/` — el borde HTTP: validación de forma (422), controlador, respuesta.
- `app/Providers/AppServiceProvider.php` — el único lugar donde se enchufan adaptadores a puertos.

## Decisiones

- **Dinero en centavos enteros.** La API recibe pesos enteros y se multiplica por 100 al entrar.
  La comparación cuota/ingreso se hace en enteros: `cuota × 100 > ingreso × porcentaje`.
- **Un centavo de diferencia con el ejemplo del enunciado.** La fórmula exacta con P = 1.000.000,
  TNA 60 % y n = 12 da 112.825,41002…, que redondeado al centavo es **112.825,41**
  (`11282541`). El enunciado muestra 112.825,40; ese valor sale de redondear algún paso
  intermedio. Elegí la fórmula exacta con un único redondeo al final; el test lo documenta.
  Cambiarlo es una línea en `CalculadoraCuota`.
- **Bordes.** "Supera el 30 %" es estrictamente mayor: al 30 % exacto se aprueba. Antigüedad de
  6 meses cumple. Monto de 5.000.000 exacto no es alto. TNA 0 divide el capital en partes iguales.
- **Motivos.** Se acumulan todos los rechazos (cuota y antigüedad pueden ir juntos).
  `MONTO_ALTO` sólo aparece si no hay rechazo. Aprobada lleva `motivos: []`.
- **Umbrales y versión de reglas** en `config/credito.php`, leídos de `.env`. Cada solicitud
  guarda `reglas_version`, así se sabe con qué reglas se decidió.
- **Idempotencia.** `idempotency_key` es `UNIQUE` en la tabla, y se guarda un hash canónico del
  cuerpo (valores normalizados, claves en orden fijo). Mismo hash → se devuelve lo guardado;
  otro hash → 409. Si dos requests idénticos llegan a la vez, el `UNIQUE` deja pasar uno; el
  otro captura la violación, relee y responde con el que ganó.
- **Bureau (opcional).** Un rechazo no lo consulta. Si el bureau falla o excede el timeout, la
  solicitud se guarda igual en `revision_manual` con `BUREAU_NO_DISPONIBLE`, conservando otros
  motivos. Para simularlo: `BUREAU_TASA_FALLO=1` o `BUREAU_LATENCIA_MS=3000` en `.env`.
- **Laravel** porque es donde llego más rápido a código que corre. El dominio no depende de él:
  montarlo sobre Symfony es reemplazar controlador, validación y repositorio.

## Qué quedó afuera

Autenticación y límites de uso. Historial de umbrales por fecha (hoy hay una versión vigente).
Reintentos o cola para el bureau. El informe del bureau no alimenta ninguna regla, porque el
enunciado no lo pide.
