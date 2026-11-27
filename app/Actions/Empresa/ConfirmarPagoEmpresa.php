<?php

namespace App\Actions\Empresa;

use App\Enums\EstadoProceso;
use App\Models\CandidatoSolicitud;
use App\Models\PagoProceso;
use App\Models\Postulacion;
use App\Models\Proceso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmarPagoEmpresa
{
    public function __construct(private ActivarCandidatosEmpresa $activar) {}

    public function handle(Proceso $proceso, ?string $stripeSessionId, ?string $paymentIntentId, string $estadoPago = 'COMPLETADO'): Proceso
    {
        return DB::transaction(function () use ($proceso, $stripeSessionId, $paymentIntentId, $estadoPago): Proceso {
            $proceso = Proceso::query()->lockForUpdate()->findOrFail($proceso->id);

            if ($stripeSessionId !== null && PagoProceso::query()
                ->where('proceso_id', $proceso->id)
                ->where('stripe_checkout_session_id', $stripeSessionId)
                ->where('estado', 'COMPLETADO')
                ->exists()) {
                return $proceso;
            }

            if ($proceso->estado === EstadoProceso::EnCurso) {
                return $this->confirmarAmpliacion($proceso, $stripeSessionId, $paymentIntentId, $estadoPago);
            }

            if ($proceso->estado !== EstadoProceso::PendientePago) {
                throw ValidationException::withMessages([
                    'proceso' => 'Este proceso no está pendiente de pago.',
                ]);
            }

            $proceso->update(['estado' => EstadoProceso::PendienteCandidatos]);

            $monto = $this->montoEsperado($proceso);
            $this->registrarPago($proceso, $stripeSessionId, $paymentIntentId, $estadoPago, $monto);

            $proceso = $proceso->fresh();
            if ($this->cantidadCandidatosEvaluar($proceso) > 0) {
                $this->activar->handle($proceso, null);

                return $proceso->fresh();
            }

            return $proceso;
        });
    }

    private function confirmarAmpliacion(
        Proceso $proceso,
        ?string $stripeSessionId,
        ?string $paymentIntentId,
        string $estadoPago,
    ): Proceso {
        $pendientes = $this->candidatosPendientesDeActivacion($proceso);
        if ($pendientes === 0) {
            throw ValidationException::withMessages([
                'proceso' => 'No hay candidatos nuevos pendientes de pago.',
            ]);
        }

        return DB::transaction(function () use ($proceso, $stripeSessionId, $paymentIntentId, $estadoPago, $pendientes): Proceso {
            $monto = $pendientes * (int) config('stripe.precio_centavos_por_vacante', 1500);
            $this->registrarPago($proceso, $stripeSessionId, $paymentIntentId, $estadoPago, $monto);

            $this->activar->handle($proceso->fresh(), null);

            return $proceso->fresh();
        });
    }

    private function registrarPago(
        Proceso $proceso,
        ?string $stripeSessionId,
        ?string $paymentIntentId,
        string $estadoPago,
        int $monto,
    ): void {
        PagoProceso::query()->updateOrCreate(
            ['proceso_id' => $proceso->id],
            [
                'stripe_checkout_session_id' => $stripeSessionId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'monto_centavos' => $monto,
                'moneda' => config('stripe.moneda', 'bob'),
                'estado' => $estadoPago,
                'pagado_en' => now(),
            ],
        );
    }

    public function cantidadCandidatosEvaluar(Proceso $proceso): int
    {
        return CandidatoSolicitud::query()->where('proceso_id', $proceso->id)->count();
    }

    public function candidatosPendientesDeActivacion(Proceso $proceso): int
    {
        return $this->candidatosPendientesColeccion($proceso)->count();
    }

    /**
     * @return Collection<int, CandidatoSolicitud>
     */
    public function candidatosPendientesColeccion(Proceso $proceso): Collection
    {
        $proceso->loadMissing('solicitud.candidatos');
        $activados = Postulacion::query()
            ->where('proceso_id', $proceso->id)
            ->whereHas('persona.usuario')
            ->with('persona.usuario')
            ->get()
            ->map(fn (Postulacion $postulacion): string => mb_strtolower((string) $postulacion->persona?->usuario?->correo));

        return $proceso->solicitud?->candidatos
            ->filter(fn (CandidatoSolicitud $candidato): bool => ! $activados->contains(mb_strtolower($candidato->correo)))
            ?? collect();
    }

    public function montoEsperado(Proceso $proceso): int
    {
        if ($proceso->estado === EstadoProceso::EnCurso) {
            return $this->candidatosPendientesDeActivacion($proceso) * (int) config('stripe.precio_centavos_por_vacante', 1500);
        }

        $cantidad = $this->cantidadCandidatosEvaluar($proceso);

        return $cantidad * (int) config('stripe.precio_centavos_por_vacante', 1500);
    }
}
