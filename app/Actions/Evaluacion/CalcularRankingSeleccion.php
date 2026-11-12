<?php

namespace App\Actions\Evaluacion;

use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\RankingResultado;
use App\Models\ResultadoPrueba;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CalcularRankingSeleccion
{
    /**
     * Calcula o recalcula el ranking ponderado de candidatos para un proceso.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function handle(Proceso $proceso): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($proceso) {
            // 1. Obtener todas las etapas y pruebas con peso del proceso
            $procesoEtapas = $proceso->etapas()
                ->where('activa', true)
                ->with(['pruebas' => fn ($q) => $q->where('peso', '>', 0)->with('prueba')])
                ->get();

            $pruebasConPeso = $procesoEtapas->flatMap(fn ($pe) => $pe->pruebas);
            $sumaPesosTotal = (float) $pruebasConPeso->sum('peso');

            // 2. Obtener todas las postulaciones del proceso
            $postulaciones = Postulacion::where('proceso_id', $proceso->id)
                ->with(['persona.egresados', 'persona.usuario', 'resultados.procesoPrueba.prueba'])
                ->get();

            $pruebaPpa = $proceso->pruebaPpa();

            $candidatosCalculados = [];

            foreach ($postulaciones as $postulacion) {
                // Asegurar que si existe PPA y no está en resultados, se registre
                if ($pruebaPpa && ! $postulacion->resultados->contains('proceso_prueba_id', $pruebaPpa->id)) {
                    $egresado = $postulacion->persona?->egresados?->first();
                    $ppa = (float) ($egresado?->ppa ?? 0);
                    if ($ppa > 0) {
                        ResultadoPrueba::create([
                            'postulacion_id' => $postulacion->id,
                            'proceso_prueba_id' => $pruebaPpa->id,
                            'puntaje_bruto' => $ppa,
                            'puntaje_maximo' => 100.00,
                            'umbral_aplicado' => (float) $proceso->convocatoria?->carrera?->facultad?->ppa_minimo ?? 51.00,
                            'aprobada' => true,
                            'calificado_en' => now(),
                        ]);
                        $postulacion->load('resultados.procesoPrueba.prueba');
                    }
                }

                $desglosePruebas = [];
                $sumaPonderada = 0.0;
                $sumaPesosAplicados = 0.0;

                foreach ($pruebasConPeso as $pp) {
                    $resultado = $postulacion->resultados->firstWhere('proceso_prueba_id', $pp->id);
                    $puntajeNorm = $resultado ? (float) $resultado->puntaje_normalizado : 0.0;
                    $peso = (float) $pp->peso;

                    $desglosePruebas[] = [
                        'prueba_id' => $pp->prueba_id,
                        'codigo' => $pp->prueba?->codigo,
                        'nombre' => $pp->prueba?->nombre,
                        'peso' => $peso,
                        'puntaje_bruto' => $resultado ? (float) $resultado->puntaje_bruto : 0.0,
                        'puntaje_maximo' => $resultado ? (float) $resultado->puntaje_maximo : 0.0,
                        'puntaje_normalizado' => $puntajeNorm,
                        'aprobada' => $resultado ? (bool) $resultado->aprobada : false,
                        'aporte_ponderado' => round($puntajeNorm * ($peso / 100), 2),
                    ];

                    $sumaPonderada += $puntajeNorm * $peso;
                    $sumaPesosAplicados += $peso;
                }

                $divisor = $sumaPesosTotal > 0 ? $sumaPesosTotal : ($sumaPesosAplicados > 0 ? $sumaPesosAplicados : 100.0);
                $puntajeTotal = round($sumaPonderada / $divisor, 2);

                $candidatosCalculados[] = [
                    'postulacion_id' => $postulacion->id,
                    'codigo_postulacion' => $postulacion->codigo,
                    'postulante' => [
                        'nombre_completo' => $postulacion->persona?->nombre_completo,
                        'ci' => $postulacion->persona?->ci,
                        'correo' => $postulacion->persona?->usuario?->correo,
                    ],
                    'puntaje_total' => $puntajeTotal,
                    'desglose' => $desglosePruebas,
                ];
            }

            // 3. Ordenar descendentemente por puntaje_total
            usort($candidatosCalculados, fn ($a, $b) => $b['puntaje_total'] <=> $a['puntaje_total']);

            // 4. Asignar posiciones y top_n
            $topN = $proceso->top_n ?? 5;
            $posicion = 1;
            $rankingActual = RankingResultado::where('proceso_id', $proceso->id)->get()->keyBy('postulacion_id');

            // Limpiar registros previos temporalmente para evitar choques con uq_ranking_posicion
            RankingResultado::where('proceso_id', $proceso->id)->delete();

            $filasAGuardar = [];
            foreach ($candidatosCalculados as &$cand) {
                $esTop = $posicion <= $topN;
                $cand['posicion'] = $posicion;
                $cand['es_top'] = $esTop;

                $previo = $rankingActual->get($cand['postulacion_id']);

                $filasAGuardar[] = [
                    'proceso_id' => $proceso->id,
                    'postulacion_id' => $cand['postulacion_id'],
                    'posicion' => $posicion,
                    'puntaje_total' => $cand['puntaje_total'],
                    'es_top' => $esTop,
                    'visible' => $previo ? $previo->visible : false,
                    'publicado_en' => $previo ? $previo->publicado_en : null,
                    'publicado_por' => $previo ? $previo->publicado_por : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $posicion++;
            }

            if (! empty($filasAGuardar)) {
                DB::table('talento.ranking_resultado')->insert($filasAGuardar);
            }

            return collect($candidatosCalculados);
        });
    }
}

