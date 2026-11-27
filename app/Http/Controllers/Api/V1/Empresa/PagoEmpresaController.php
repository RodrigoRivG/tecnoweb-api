<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\ConfirmarCheckoutEmpresa;
use App\Actions\Empresa\ConfirmarPagoEmpresa;
use App\Actions\Empresa\CrearCheckoutEmpresa;
use App\Enums\EstadoProceso;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PagoEmpresaController extends Controller
{
    public function checkout(Request $request, Proceso $proceso, CrearCheckoutEmpresa $crear): JsonResponse
    {
        $this->autorizarEmpresa($request, $proceso);

        return response()->json(['data' => $crear->handle($proceso)]);
    }

    public function simular(Request $request, Proceso $proceso, ConfirmarPagoEmpresa $confirmar): JsonResponse
    {
        $this->autorizarEmpresa($request, $proceso);
        $pendientes = app(ConfirmarPagoEmpresa::class)->candidatosPendientesDeActivacion($proceso);
        if ($proceso->estado === EstadoProceso::EnCurso) {
            if ($pendientes < 1) {
                abort(422, 'No hay candidatos nuevos por pagar.');
            }
        } elseif ($proceso->estado !== EstadoProceso::PendientePago) {
            abort(422, 'La solicitud no está pendiente de pago.');
        }
        if (config('stripe.secret')) {
            abort(403, 'Usa Stripe Checkout para pagar. La simulación solo aplica sin clave de Stripe.');
        }
        if (! config('stripe.simular_sin_clave')) {
            abort(403, 'La simulación de pago está deshabilitada.');
        }

        $confirmar->handle($proceso, null, null, 'SIMULADO');
        $proceso = $proceso->fresh();

        return response()->json([
            'data' => [
                'proceso_id' => $proceso->id,
                'estado' => $proceso->estado->value,
            ],
        ]);
    }

    public function confirmarCheckout(Request $request, Proceso $proceso, ConfirmarCheckoutEmpresa $confirmar): JsonResponse
    {
        $this->autorizarEmpresa($request, $proceso);
        $datos = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        $proceso = $confirmar->handle($proceso, $datos['session_id']);

        return response()->json([
            'data' => [
                'proceso_id' => $proceso->id,
                'estado' => $proceso->estado->value,
            ],
        ]);
    }

    public function webhook(Request $request, ConfirmarPagoEmpresa $confirmar): JsonResponse
    {
        $secret = config('stripe.webhook_secret');
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! $secret) {
            return response()->json(['error' => 'Webhook no configurado'], 503);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Firma inválida'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $procesoId = (int) ($session->metadata->proceso_id ?? $session->client_reference_id ?? 0);
            if ($procesoId > 0) {
                $proceso = Proceso::query()->find($procesoId);
                if ($proceso !== null && in_array($proceso->estado, [EstadoProceso::PendientePago, EstadoProceso::EnCurso], true)) {
                    $confirmar->handle(
                        $proceso,
                        $session->id,
                        is_string($session->payment_intent) ? $session->payment_intent : null,
                    );
                }
            }
        }

        return response()->json(['received' => true]);
    }

    private function autorizarEmpresa(Request $request, Proceso $proceso): void
    {
        $usuarioId = $request->user()->id;
        $dueno = Proceso::query()
            ->whereKey($proceso->id)
            ->where('tipo', 'EMPRESA')
            ->whereHas('solicitud.contacto.persona', fn ($q) => $q->where('usuario_id', $usuarioId))
            ->exists();
        abort_unless($dueno, 403);
    }
}