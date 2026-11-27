<?php

namespace App\Actions\Empresa;

use App\Actions\Empresa\CompletarDatosSolicitudEmpresa;
use App\Enums\EstadoProceso;
use App\Models\PagoProceso;
use App\Models\Proceso;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CrearCheckoutEmpresa
{
  public function __construct(private ConfirmarPagoEmpresa $confirmar) {}

  /**
   * @return array{url: string, modo: string, proceso_id: int, monto_centavos: int}
   */
  public function handle(Proceso $proceso): array
  {
    $proceso->loadMissing('solicitud');
    $esAmpliacion = $proceso->estado === EstadoProceso::EnCurso;

    if (! in_array($proceso->estado, [EstadoProceso::PendientePago, EstadoProceso::EnCurso], true)) {
      throw ValidationException::withMessages([
        'proceso' => 'Este proceso no admite pago en este momento.',
      ]);
    }

    if (! $esAmpliacion && CompletarDatosSolicitudEmpresa::datosPendientes($proceso->solicitud->cargo)) {
      throw ValidationException::withMessages([
        'proceso' => 'Define el puesto, las vacantes y el perfil ideal antes de pagar.',
      ]);
    }

    $cantidad = $esAmpliacion
      ? $this->confirmar->candidatosPendientesDeActivacion($proceso)
      : $this->confirmar->cantidadCandidatosEvaluar($proceso);
    if ($cantidad < 1) {
      throw ValidationException::withMessages([
        'proceso' => $esAmpliacion
          ? 'Agrega candidatos nuevos antes de pagar la ampliación.'
          : 'Registra los candidatos que harán el examen antes de pagar.',
      ]);
    }

    $monto = $cantidad * (int) config('stripe.precio_centavos_por_vacante', 1500);
    $simular = ! config('stripe.secret') && config('stripe.simular_sin_clave');

    if ($simular) {
      PagoProceso::query()->updateOrCreate(
        ['proceso_id' => $proceso->id],
        [
          'monto_centavos' => $monto,
          'moneda' => config('stripe.moneda', 'usd'),
          'estado' => 'PENDIENTE',
        ],
      );

      return [
        'modo' => 'simulado',
        'url' => null,
        'proceso_id' => $proceso->id,
        'monto_centavos' => $monto,
        'cantidad_candidatos' => $cantidad,
        'ampliacion' => $esAmpliacion,
      ];
    }

    Stripe::setApiKey(config('stripe.secret'));
    $baseFront = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

    $session = Session::create([
      'mode' => 'payment',
      'success_url' => "{$baseFront}/empresa/solicitudes/{$proceso->id}/candidatos?pago=ok&session_id={CHECKOUT_SESSION_ID}",
      'cancel_url' => "{$baseFront}/empresa/solicitudes/{$proceso->id}/pago?cancelado=1",
      'client_reference_id' => (string) $proceso->id,
      'metadata' => ['proceso_id' => (string) $proceso->id],
      'line_items' => [[
        'quantity' => $cantidad,
        'price_data' => [
          'currency' => config('stripe.moneda', 'usd'),
          'unit_amount' => (int) config('stripe.precio_centavos_por_vacante', 1500),
          'product_data' => [
            'name' => 'Evaluación de personal — '.$proceso->solicitud->cargo,
            'description' => "{$cantidad} candidato(s) a evaluar · proceso {$proceso->codigo}",
          ],
        ],
      ]],
    ]);

    PagoProceso::query()->updateOrCreate(
      ['proceso_id' => $proceso->id],
      [
        'stripe_checkout_session_id' => $session->id,
        'monto_centavos' => $monto,
        'moneda' => config('stripe.moneda', 'usd'),
        'estado' => 'PENDIENTE',
      ],
    );

    return [
      'modo' => 'stripe',
      'url' => $session->url,
      'proceso_id' => $proceso->id,
      'monto_centavos' => $monto,
      'cantidad_candidatos' => $cantidad,
      'ampliacion' => $esAmpliacion,
    ];
  }
}