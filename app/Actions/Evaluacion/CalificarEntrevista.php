<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\MotivoNoAprobacion;
use App\Models\Postulacion;
use App\Models\ProcesoPrueba;
use App\Models\ResultadoPrueba;
use App\Models\RubricaCriterio;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalificarEntrevista
{
    /**
     * Califica la rúbrica de Entrevista Personal (Etapa 7) por parte del tribunal/comisión.
     *
     * @param array{
     *   calificaciones: array<int, array{criterio_id: int, puntaje: float, comentario?: ?string}>,
     *   observaciones?: ?string
     * } $datos
     */
    public function handle(Usuario $evaluador, Postulacion $postulacion, array $datos): ResultadoPrueba
    {
        $etapaEntrevista = $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
            ->first();

        if ($etapaEntrevista === null) {
            throw ValidationException::withMessages([
                'postulacion' => 'La postulación no cuenta con etapa de Entrevista.',
            ]);
        }

        if ($etapaEntrevista->estado === EstadoEtapa::Bloqueada) {
            throw ValidationException::withMessages([
                'etapa' => 'La etapa de Entrevista se encuentra bloqueada. Debe superar el Análisis de CV.',
            ]);
        }

        $procesoPrueba = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)
            ->whereHas('prueba', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
            ->first();

        if ($procesoPrueba === null) {
            throw ValidationException::withMessages([
                'prueba' => 'No se encontró la prueba configurada para Entrevista.',
            ]);
        }

        $criterios = RubricaCriterio::where('prueba_id', $procesoPrueba->prueba_id)->get()->keyBy('id');
        if ($criterios->isEmpty()) {
            throw ValidationException::withMessages([
                'criterios' => 'No existen criterios de rúbrica registrados para la Entrevista.',
            ]);
        }

        return Auditoria::comoUsuario($evaluador->id, function () use ($evaluador, $postulacion, $etapaEntrevista, $procesoPrueba, $criterios, $datos): ResultadoPrueba {
            $puntajeBruto = 0.0;
            $puntajeMaximo = 0.0;
            $calificacionesData = [];

            foreach ($criterios as $criterio) {
                $puntajeMaximo += (float) $criterio->puntaje_maximo;
            }

            foreach ($datos['calificaciones'] as $calif) {
                $criterioId = (int) $calif['criterio_id'];
                $criterio = $criterios->get($criterioId);
                if (! $criterio) {
                    throw ValidationException::withMessages([
                        'criterio' => "El criterio con ID {$criterioId} no pertenece a la rúbrica de Entrevista.",
                    ]);
                }

                $puntaje = max(0.0, min((float) $criterio->puntaje_maximo, (float) $calif['puntaje']));
                $puntajeBruto += $puntaje;

                $calificacionesData[] = [
                    'criterio_id' => $criterioId,
                    'puntaje' => $puntaje,
                    'comentario' => $calif['comentario'] ?? null,
                ];
            }

            $umbral = $procesoPrueba->puntaje_minimo ?? 50.00;
            $puntajeNormalizado = $puntajeMaximo > 0 ? round(($puntajeBruto / $puntajeMaximo) * 100, 2) : 0.0;
            $aprobada = $puntajeNormalizado >= (float) $umbral;

            // Registrar o actualizar ResultadoPrueba
            $resultado = ResultadoPrueba::updateOrCreate(
                [
                    'postulacion_id' => $postulacion->id,
                    'proceso_prueba_id' => $procesoPrueba->id,
                ],
                [
                    'puntaje_bruto' => $puntajeBruto,
                    'puntaje_maximo' => $puntajeMaximo,
                    'umbral_aplicado' => $umbral,
                    'aprobada' => $aprobada,
                    'motivo_no_aprobacion' => $aprobada ? null : MotivoNoAprobacion::PuntajeBajo,
                    'evaluador_id' => $evaluador->id,
                    'observaciones' => $datos['observaciones'] ?? null,
                    'calificado_en' => now(),
                ]
            );

            // Guardar calificaciones por criterio
            foreach ($calificacionesData as $calif) {
                DB::table('talento.calificacion_criterio')->updateOrInsert(
                    [
                        'resultado_prueba_id' => $resultado->id,
                        'criterio_id' => $calif['criterio_id'],
                    ],
                    [
                        'puntaje' => $calif['puntaje'],
                        'comentario' => $calif['comentario'],
                        'updated_at' => now(),
                    ]
                );
            }

            // Actualizar etapa de Entrevista
            if ($aprobada) {
                $etapaEntrevista->update([
                    'estado' => EstadoEtapa::Aprobada,
                    'finalizada_en' => now(),
                ]);

                // Desbloquear la etapa final (Etapa 8: Selección)
                $ordenActual = $etapaEntrevista->procesoEtapa->orden;
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
            } else {
                $etapaEntrevista->update([
                    'estado' => EstadoEtapa::Reprobada,
                    'finalizada_en' => now(),
                ]);

                $postulacion->update([
                    'estado' => EstadoPostulacion::NoAprobada,
                ]);
            }

            return $resultado->load(['calificacionesCriterios.criterio', 'postulacion.etapas.procesoEtapa.etapa']);
        });
    }
}

