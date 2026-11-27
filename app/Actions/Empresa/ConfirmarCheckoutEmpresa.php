<?php

namespace App\Actions\Empresa;

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class ConfirmarCheckoutEmpresa
{
    public function __construct(private ConfirmarPagoEmpresa $confirmar) {}

    public function handle(Proceso $proceso, string $sessionId): Proceso
    {
        if (! config('stripe.secret')) {
            throw ValidationException::withMessages([
                'stripe' => 'Stripe no está configurado.',
            ]);
        }

        if ($proceso->estado === EstadoProceso::PendienteCandidatos) {
            return $proceso;
        }
        if ($proceso->estado === EstadoProceso::EnCurso && $this->confirmar->candidatosPendientesDeActivacion($proceso) < 1) {
            return $proceso;
        }

        Stripe::setApiKey(config('stripe.secret'));
        $session = Session::retrieve($sessionId);

        $procesoId = (int) ($session->metadata['proceso_id'] ?? $session->client_reference_id ?? 0);
        if ($procesoId !== $proceso->id) {
            throw ValidationException::withMessages([
                'session_id' => 'Esta sesión de pago no corresponde a la solicitud.',
            ]);
        }

        if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
            throw ValidationException::withMessages([
                'session_id' => 'El pago aún no está confirmado. Espera unos segundos e intenta de nuevo.',
            ]);
        }

        if (! in_array($proceso->estado, [EstadoProceso::PendientePago, EstadoProceso::EnCurso], true)) {
            return $proceso->fresh();
        }

        $paymentIntent = is_string($session->payment_intent) ? $session->payment_intent : null;

        return $this->confirmar->handle($proceso, $session->id, $paymentIntent);
    }
}