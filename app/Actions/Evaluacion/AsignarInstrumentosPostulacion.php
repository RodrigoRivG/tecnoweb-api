<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use Illuminate\Support\Collection;

/**
 * Elige el libro de cada cupo cuando la etapa ya está habilitada.
 * Personalidad, inteligencia y habilidades blandas rotan.
 * Las habilidades específicas conservan el libro fijo de la carrera.
 */
class AsignarInstrumentosPostulacion
{
    public function handle(Postulacion $postulacion): void
    {
        $postulacion->loadMissing(['etapas', 'proceso.etapas.pruebas.prueba']);

        $etapas = $postulacion->etapas->keyBy('proceso_etapa_id');
        $yaAsignados = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->pluck('proceso_prueba_id');

        foreach ($postulacion->proceso->etapas as $etapa) {
            foreach ($etapa->pruebas as $cupo) {
                if ($yaAsignados->contains($cupo->id) || $cupo->prueba?->tipo !== TipoPrueba::Test) {
                    continue;
                }

                $etapaPostulacion = $etapas->get($cupo->proceso_etapa_id);
                if (! $this->etapaHabilitada($etapaPostulacion)) {
                    continue;
                }

                AsignacionPrueba::query()->create([
                    'postulacion_id' => $postulacion->id,
                    'proceso_prueba_id' => $cupo->id,
                    'prueba_id' => $this->elegir($postulacion, $cupo),
                ]);

                $yaAsignados->push($cupo->id);
            }
        }
    }

    private function etapaHabilitada(?PostulacionEtapa $etapa): bool
    {
        return in_array($etapa?->estado, [
            EstadoEtapa::Pendiente,
            EstadoEtapa::EnCurso,
            EstadoEtapa::EnRevision,
            EstadoEtapa::Aprobada,
            EstadoEtapa::Reprobada,
        ], true);
    }

    private function elegir(Postulacion $postulacion, ProcesoPrueba $cupo): int
    {
        $familia = $cupo->prueba?->familia;
        if ($familia === null || ! $familia->alterna()) {
            return $cupo->prueba_id;
        }

        $candidatas = Prueba::query()
            ->where('familia', $familia)
            ->where('activa', true)
            ->where('tipo', TipoPrueba::Test)
            ->whereHas('preguntas')
            ->orderBy('id')
            ->get();

        if ($candidatas->isEmpty()) {
            return $cupo->prueba_id;
        }

        $conteos = AsignacionPrueba::query()
            ->whereIn('prueba_id', $candidatas->pluck('id'))
            ->whereHas('postulacion', fn ($consulta) => $consulta->where('proceso_id', $postulacion->proceso_id))
            ->selectRaw('prueba_id, count(*) as total')
            ->groupBy('prueba_id')
            ->pluck('total', 'prueba_id');

        /** @var Collection<int, Prueba> $candidatas */
        return $candidatas
            ->sortBy(fn (Prueba $prueba): array => [(int) ($conteos[$prueba->id] ?? 0), $prueba->id])
            ->first()
            ->id;
    }
}
