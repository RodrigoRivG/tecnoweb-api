<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\Postulacion;
use App\Models\ProcesoEtapa;
use App\Models\ResultadoPrueba;
use Illuminate\Support\Collection;

/**
 * Cierra una etapa con pruebas cuando el postulante tiene resultado en cada obligatoria asignada.
 */
class AvanzarEtapaTrasResultados
{
    public function sincronizarPostulacion(Postulacion $postulacion): void
    {
        $postulacion->loadMissing('etapas.procesoEtapa');

        foreach ($postulacion->etapas as $etapaPostulacion) {
            $procesoEtapa = $etapaPostulacion->procesoEtapa;
            if ($procesoEtapa && $procesoEtapa->pruebas()->exists()) {
                $this->handle($postulacion, $procesoEtapa);
            }
        }
    }

    public function handle(Postulacion $postulacion, ProcesoEtapa $procesoEtapa): void
    {
        $requeridas = $this->idsPruebasRequeridas($postulacion, $procesoEtapa);
        if ($requeridas->isEmpty()) {
            return;
        }

        $completadas = ResultadoPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $requeridas)
            ->count();

        if ($completadas < $requeridas->count()) {
            return;
        }

        $etapaActual = $postulacion->etapas()
            ->where('proceso_etapa_id', $procesoEtapa->id)
            ->first();

        if (! $etapaActual || $etapaActual->estado === EstadoEtapa::Aprobada) {
            return;
        }

        $etapaActual->update([
            'estado' => EstadoEtapa::Aprobada,
            'finalizada_en' => now(),
        ]);

        $this->desbloquearSiguienteEtapa($postulacion, $procesoEtapa->orden);
    }

    private function idsPruebasRequeridas(Postulacion $postulacion, ProcesoEtapa $procesoEtapa): Collection
    {
        $obligatorias = $procesoEtapa->pruebas()->with('prueba')->where('obligatoria', true)->get();
        $deFamilia = $obligatorias->filter(
            fn ($cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test && $cupo->prueba->familia !== null,
        );
        $asignadas = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $deFamilia->pluck('id'))
            ->pluck('proceso_prueba_id');

        return $obligatorias
            ->reject(fn ($cupo): bool => $deFamilia->contains('id', $cupo->id))
            ->pluck('id')
            ->merge($asignadas)
            ->unique()
            ->values();
    }

    private function desbloquearSiguienteEtapa(Postulacion $postulacion, int $ordenActual): void
    {
        $siguienteEtapa = $postulacion->etapas()
            ->whereHas('procesoEtapa', fn ($q) => $q->where('orden', '>', $ordenActual)->where('activa', true))
            ->join('talento.proceso_etapa', 'talento.postulacion_etapa.proceso_etapa_id', '=', 'talento.proceso_etapa.id')
            ->orderBy('talento.proceso_etapa.orden')
            ->select('talento.postulacion_etapa.*')
            ->first();

        if ($siguienteEtapa && $siguienteEtapa->estado === EstadoEtapa::Bloqueada) {
            $siguienteEtapa->update([
                'estado' => EstadoEtapa::Pendiente,
                'iniciada_en' => now(),
            ]);
        }
    }
}