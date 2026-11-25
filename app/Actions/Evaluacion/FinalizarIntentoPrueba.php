<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoIntento;
use App\Enums\MotivoNoAprobacion;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\Pregunta;
use App\Models\ResultadoDimension;
use App\Models\ResultadoPrueba;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizarIntentoPrueba
{
    public function handle(Usuario $usuario, IntentoPrueba $intento): ResultadoPrueba
    {
        if ($intento->postulacion->persona_id !== $usuario->persona?->id) {
            throw ValidationException::withMessages([
                'intento' => 'No tiene autorización sobre este intento de evaluación.',
            ]);
        }

        if ($intento->estado !== EstadoIntento::EnCurso) {
            if ($intento->estado === EstadoIntento::Finalizado) {
                $resultadoExistente = ResultadoPrueba::where('intento_id', $intento->id)->first();
                if ($resultadoExistente) {
                    return $resultadoExistente;
                }
            }

            throw ValidationException::withMessages([
                'intento' => 'El intento no se encuentra en curso.',
            ]);
        }

        return Auditoria::comoUsuario($usuario->id, function () use ($intento): ResultadoPrueba {
            $procesoPrueba = $intento->procesoPrueba;
            $prueba = $procesoPrueba->prueba;
            $postulacion = $intento->postulacion;

            // 1. Finalizar intento
            $intento->update([
                'estado' => EstadoIntento::Finalizado,
                'finalizado_en' => now(),
            ]);

            // 2. Calcular puntaje máximo posible de las preguntas asignadas a este intento
            $preguntasAsignadasIds = $intento->respuestas()->pluck('pregunta_id');

            if ($preguntasAsignadasIds->isNotEmpty()) {
                $preguntas = Pregunta::whereIn('id', $preguntasAsignadasIds)->with('opciones')->get();
            } else {
                $preguntas = Pregunta::where('prueba_id', $prueba->id)->with('opciones')->get();
            }

            $puntajeMaximo = 0.0;
            foreach ($preguntas as $p) {
                $maxOpcion = $p->opciones->max('puntaje') ?? 1.0;
                $puntajeMaximo += (float) $maxOpcion;
            }

            if ($puntajeMaximo <= 0) {
                $puntajeMaximo = 100.0;
            }

            // 3. Calcular puntaje bruto obtenido a través de las opciones marcadas
            $puntajeBruto = (float) DB::table('talento.respuesta_intento as r')
                ->join('talento.opcion_pregunta as o', 'r.opcion_id', '=', 'o.id')
                ->where('r.intento_id', $intento->id)
                ->sum('o.puntaje');

            // Asegurar que puntaje bruto no exceda el máximo
            $puntajeBruto = min($puntajeBruto, $puntajeMaximo);

            // 4. Evaluar umbral de aprobación
            $umbral = $procesoPrueba->puntaje_minimo ?? 50.00;
            $puntajeNormalizado = round(($puntajeBruto / $puntajeMaximo) * 100, 2);
            $aprobada = $puntajeNormalizado >= (float) $umbral;
            $tiempoAgotado = false;

            // 5. Registrar resultado de la prueba
            $resultado = ResultadoPrueba::updateOrCreate(
                [
                    'postulacion_id' => $postulacion->id,
                    'proceso_prueba_id' => $procesoPrueba->id,
                ],
                [
                    'intento_id' => $intento->id,
                    'puntaje_bruto' => $puntajeBruto,
                    'puntaje_maximo' => $puntajeMaximo,
                    'umbral_aplicado' => $umbral,
                    'aprobada' => $aprobada,
                    'motivo_no_aprobacion' => $aprobada ? null : ($tiempoAgotado ? MotivoNoAprobacion::TiempoAgotado : MotivoNoAprobacion::PuntajeBajo),
                    'calificado_en' => now(),
                ]
            );

            // 5.1 Calcular y registrar valores para cada dimensión psicológica (decatipos 1-10)
            $dimensionesIds = $preguntas->pluck('dimension_id')->filter()->unique();

            $respuestasIntento = DB::table('talento.respuesta_intento as r')
                ->join('talento.opcion_pregunta as o', 'r.opcion_id', '=', 'o.id')
                ->where('r.intento_id', $intento->id)
                ->select('r.pregunta_id', 'o.puntaje')
                ->get()
                ->keyBy('pregunta_id');

            foreach ($dimensionesIds as $dimId) {
                $preguntasDim = $preguntas->where('dimension_id', $dimId);
                $puntosObtenidosDim = 0.0;
                $puntosMaximosDim = 0.0;
                $respondidasEnDim = 0;

                foreach ($preguntasDim as $pDim) {
                    if ($respuestasIntento->has($pDim->id)) {
                        $puntosObtenidosDim += (float) $respuestasIntento->get($pDim->id)->puntaje;
                        $puntosMaximosDim += (float) ($pDim->opciones->max('puntaje') ?? 2.0);
                        $respondidasEnDim++;
                    }
                }

                // Escala de decatipos estándar 16PF (1 a 10):
                // Si respondió preguntas de esta dimensión, se calcula según su desempeño.
                // Si aún no respondió ninguna de este factor, se sitúa en la norma neutra poblacional (5.5).
                if ($puntosMaximosDim > 0) {
                    $ratio = $puntosObtenidosDim / $puntosMaximosDim;
                    $decatipo = round(1.0 + ($ratio * 9.0), 2);
                } else {
                    $decatipo = 5.50;
                }

                $decatipo = max(1.0, min(10.0, $decatipo));

                ResultadoDimension::updateOrCreate(
                    [
                        'resultado_prueba_id' => $resultado->id,
                        'dimension_id' => $dimId,
                    ],
                    [
                        'valor' => $decatipo,
                        'puntuacion_bruta' => round($puntosObtenidosDim, 2),
                    ]
                );
            }

            // 6. Si fue aprobada, verificar si TODAS las pruebas obligatorias de la etapa están aprobadas
            if ($aprobada) {
                $procesoEtapa = $procesoPrueba->procesoEtapa;
                $obligatorias = $procesoEtapa->pruebas()->with('prueba')->where('obligatoria', true)->get();
                $deFamilia = $obligatorias->filter(
                    fn ($cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test && $cupo->prueba->familia !== null,
                );
                $asignadas = AsignacionPrueba::query()
                    ->where('postulacion_id', $postulacion->id)
                    ->whereIn('proceso_prueba_id', $deFamilia->pluck('id'))
                    ->pluck('proceso_prueba_id');
                $requeridas = $obligatorias
                    ->reject(fn ($cupo): bool => $deFamilia->contains('id', $cupo->id))
                    ->pluck('id')
                    ->merge($asignadas)
                    ->unique()
                    ->values();

                $aprobadasCount = ResultadoPrueba::where('postulacion_id', $postulacion->id)
                    ->whereIn('proceso_prueba_id', $requeridas)
                    ->where('aprobada', true)
                    ->count();

                $todasAprobadas = $requeridas->isNotEmpty()
                    && ($deFamilia->isEmpty() || $asignadas->isNotEmpty())
                    && $aprobadasCount >= $requeridas->count();

                if ($todasAprobadas) {
                    $etapaActual = $postulacion->etapas()
                        ->where('proceso_etapa_id', $procesoEtapa->id)
                        ->first();

                    if ($etapaActual) {
                        $etapaActual->update([
                            'estado' => EstadoEtapa::Aprobada,
                            'finalizada_en' => now(),
                        ]);

                        // Desbloquear la siguiente etapa activa
                        $ordenActual = $etapaActual->procesoEtapa->orden;

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
            }

            app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());

            return $resultado;
        });
    }
}
