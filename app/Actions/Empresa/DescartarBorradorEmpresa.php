<?php

namespace App\Actions\Empresa;

use App\Enums\EstadoProceso;
use App\Models\CandidatoSolicitud;
use App\Models\PagoProceso;
use App\Models\Postulacion;
use App\Models\Proceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DescartarBorradorEmpresa
{
    public function handle(Proceso $proceso): void
    {
        $proceso->loadMissing('solicitud');

        if ($proceso->tipo->value !== 'EMPRESA') {
            throw ValidationException::withMessages(['proceso' => 'Solo aplica a vacantes de empresa.']);
        }

        if ($proceso->estado !== EstadoProceso::PendientePago) {
            throw ValidationException::withMessages([
                'proceso' => 'Solo puedes descartar vacantes que aún no están pagadas ni en curso.',
            ]);
        }

        if (Postulacion::query()->where('proceso_id', $proceso->id)->exists()) {
            throw ValidationException::withMessages([
                'proceso' => 'Esta vacante ya tiene candidatos activos; no se puede eliminar.',
            ]);
        }

        if (CandidatoSolicitud::query()->where('proceso_id', $proceso->id)->exists()) {
            throw ValidationException::withMessages([
                'proceso' => 'Hay candidatos guardados en esta vacante. Elimínalos antes o continúa con el pago.',
            ]);
        }

        $pago = PagoProceso::query()->where('proceso_id', $proceso->id)->first();
        if ($pago !== null && in_array($pago->estado, ['COMPLETADO', 'SIMULADO'], true)) {
            throw ValidationException::withMessages([
                'proceso' => 'Esta vacante ya tiene un pago registrado y no se puede descartar.',
            ]);
        }

        $proceso->load('etapas.pruebas', 'solicitud');

        DB::transaction(function () use ($proceso): void {
            DB::table('talento.perfil_requerido')->where('proceso_id', $proceso->id)->delete();
            PagoProceso::query()->where('proceso_id', $proceso->id)->delete();
            CandidatoSolicitud::query()->where('proceso_id', $proceso->id)->delete();

            foreach ($proceso->etapas as $etapa) {
                $etapa->pruebas()->delete();
            }
            $proceso->etapas()->delete();

            $proceso->solicitud?->delete();
            $proceso->delete();
        });
    }
}