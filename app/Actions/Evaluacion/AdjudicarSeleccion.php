<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\RankingResultado;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;

class AdjudicarSeleccion
{
    /**
     * Publica el ranking final y adjudica las plazas a los postulantes seleccionados.
     *
     * @param array<int>|null $postulacionesAdjudicadasIds Si es nulo, adjudica a todos los marcados como es_top.
     * @return array<string, mixed>
     */
    public function handle(Usuario $evaluador, Proceso $proceso, ?array $postulacionesAdjudicadasIds = null): array
    {
        return Auditoria::comoUsuario($evaluador->id, function () use ($evaluador, $proceso, $postulacionesAdjudicadasIds): array {
            return DB::transaction(function () use ($evaluador, $proceso, $postulacionesAdjudicadasIds) {
                // 1. Recalcular el ranking para asegurar total exactitud
                app(CalcularRankingSeleccion::class)->handle($proceso);

                $rankings = RankingResultado::where('proceso_id', $proceso->id)
                    ->with('postulacion.persona.usuario')
                    ->orderBy('posicion')
                    ->get();

                if ($postulacionesAdjudicadasIds === null) {
                    $postulacionesAdjudicadasIds = $rankings->where('es_top', true)->pluck('postulacion_id')->all();
                }

                $ahora = now();

                // 2. Publicar resultados en el ranking
                DB::table('talento.ranking_resultado')
                    ->where('proceso_id', $proceso->id)
                    ->update([
                        'visible' => true,
                        'publicado_en' => $ahora,
                        'publicado_por' => $evaluador->id,
                        'updated_at' => $ahora,
                    ]);

                // 3. Actualizar etapas de selección y estado de las postulaciones
                $postulaciones = Postulacion::where('proceso_id', $proceso->id)->with('etapas.procesoEtapa.etapa')->get();

                $adjudicados = [];
                $noAdjudicados = [];

                foreach ($postulaciones as $postulacion) {
                    $etapaSeleccion = $postulacion->etapas
                        ->first(fn ($e) => $e->procesoEtapa?->etapa?->codigo === 'SELECCION');

                    $esAdjudicado = in_array($postulacion->id, $postulacionesAdjudicadasIds, true);

                    if ($esAdjudicado) {
                        if ($etapaSeleccion) {
                            $etapaSeleccion->update([
                                'estado' => EstadoEtapa::Aprobada,
                                'finalizada_en' => $ahora,
                            ]);
                        }
                        $postulacion->update([
                            'estado' => EstadoPostulacion::Aprobada,
                        ]);
                        $adjudicados[] = $postulacion->id;
                    } else {
                        // Solo evaluar reprobación si la postulación realmente alcanzó la etapa de selección
                        if ($etapaSeleccion && $etapaSeleccion->estado !== EstadoEtapa::Bloqueada) {
                            $etapaSeleccion->update([
                                'estado' => EstadoEtapa::Reprobada,
                                'finalizada_en' => $ahora,
                            ]);

                            if ($postulacion->estado === EstadoPostulacion::EnProceso) {
                                $postulacion->update([
                                    'estado' => EstadoPostulacion::NoAprobada,
                                ]);
                            }
                            $noAdjudicados[] = $postulacion->id;
                        }
                    }
                }

                return [
                    'proceso_id' => $proceso->id,
                    'publicado_en' => $ahora->toIso8601String(),
                    'publicado_por' => $evaluador->correo,
                    'adjudicados_count' => count($adjudicados),
                    'no_adjudicados_count' => count($noAdjudicados),
                    'ranking' => $rankings->fresh()->map(fn ($r) => [
                        'posicion' => $r->posicion,
                        'postulacion_id' => $r->postulacion_id,
                        'codigo' => $r->postulacion?->codigo,
                        'postulante' => $r->postulacion?->persona?->nombre_completo,
                        'puntaje_total' => (float) $r->puntaje_total,
                        'es_top' => $r->es_top,
                        'adjudicado' => in_array($r->postulacion_id, $adjudicados, true),
                    ]),
                ];
            });
        });
    }
}

