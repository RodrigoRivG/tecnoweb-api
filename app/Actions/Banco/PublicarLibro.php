<?php

namespace App\Actions\Banco;

use App\Enums\EstadoProceso;
use App\Enums\ModoAsignacion;
use App\Enums\UmbralOrigen;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\Proceso;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\ResultadoPrueba;

/**
 * Un libro que rota entra al sorteo por estar activo.
 * Uno de habilidad específica se agrega como cupo fijo en las convocatorias abiertas de sus carreras.
 */
class PublicarLibro
{
    public function handle(Prueba $prueba): void
    {
        if ($prueba->familia === null || $prueba->familia->alterna()) {
            return;
        }

        $prueba->load('carreras');
        $carreraIds = $prueba->carreras->pluck('id');

        ProcesoPrueba::query()
            ->where('prueba_id', $prueba->id)
            ->with('procesoEtapa.proceso.convocatoria', 'procesoEtapa.proceso.solicitud')
            ->get()
            ->each(function (ProcesoPrueba $cupo) use ($prueba, $carreraIds): void {
                $proceso = $cupo->procesoEtapa?->proceso;
                $carreraId = $proceso?->convocatoria?->carrera_id ?? $proceso?->solicitud?->carrera_id;
                $sigue = $prueba->activa && $carreraIds->contains($carreraId);
                if ($sigue || $this->ocupado($cupo)) {
                    return;
                }

                $cupo->delete();
            });

        if (! $prueba->activa || $carreraIds->isEmpty()) {
            return;
        }

        $estados = [
            EstadoProceso::Borrador,
            EstadoProceso::Configuracion,
            EstadoProceso::PendienteAprobacion,
            EstadoProceso::EnCurso,
            EstadoProceso::Abierto,
            EstadoProceso::EnEvaluacion,
        ];

        $procesos = Proceso::query()
            ->whereIn('estado', $estados)
            ->where(function ($consulta) use ($carreraIds): void {
                $consulta->whereHas('convocatoria', fn ($convocatoria) => $convocatoria->whereIn('carrera_id', $carreraIds))
                    ->orWhereHas('solicitud', fn ($solicitud) => $solicitud->whereIn('carrera_id', $carreraIds));
            })
            ->with(['etapas.etapa', 'etapas.pruebas'])
            ->get();

        foreach ($procesos as $proceso) {
            $etapa = $proceso->etapas->first(fn ($item) => $item->etapa?->codigo === 'EVALUACION_PSICOLOGICA');
            if ($etapa === null || $etapa->pruebas->contains(fn (ProcesoPrueba $cupo) => (int) $cupo->prueba_id === (int) $prueba->id)) {
                continue;
            }

            $etapa->pruebas()->create([
                'prueba_id' => $prueba->id,
                'modo_asignacion' => ModoAsignacion::Fija,
                'orden' => ((int) $etapa->pruebas->max('orden')) + 1,
                'peso' => 5,
                'umbral_origen' => UmbralOrigen::Fijo,
                'obligatoria' => true,
                'tiempo_maximo_seg' => $prueba->tiempo_limite_seg,
                'limite_preguntas' => null,
                'max_intentos' => 1,
            ]);
        }
    }

    private function ocupado(ProcesoPrueba $cupo): bool
    {
        return IntentoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || ResultadoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || AsignacionPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists();
    }
}
