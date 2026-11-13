<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoIntento;
use App\Models\IntentoPrueba;
use App\Models\Postulacion;
use App\Models\Pregunta;
use App\Models\ProcesoPrueba;
use App\Models\RespuestaIntento;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;

class IniciarIntentoPrueba
{
    public function handle(Usuario $usuario, Postulacion $postulacion, ProcesoPrueba $procesoPrueba): IntentoPrueba
    {
        if ($postulacion->persona_id !== $usuario->persona?->id) {
            throw ValidationException::withMessages([
                'postulacion' => 'No tiene autorización sobre esta postulación.',
            ]);
        }

        // Validar que la etapa esté en estado PENDIENTE
        $etapaPostulacion = $postulacion->etapas()
            ->where('proceso_etapa_id', $procesoPrueba->proceso_etapa_id)
            ->first();

        if (! $etapaPostulacion || $etapaPostulacion->estado !== EstadoEtapa::Pendiente) {
            throw ValidationException::withMessages([
                'etapa' => 'La etapa de evaluación correspondiente no se encuentra habilitada para rendir.',
            ]);
        }

        return Auditoria::comoUsuario($usuario->id, function () use ($postulacion, $procesoPrueba): IntentoPrueba {
            // Verificar si ya existe un intento en curso
            $intentoEnCurso = IntentoPrueba::where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $procesoPrueba->id)
                ->where('estado', EstadoIntento::EnCurso)
                ->first();

            if ($intentoEnCurso) {
                // Verificar si expiró el tiempo máximo
                if ($procesoPrueba->tiempo_maximo_seg !== null) {
                    $segundosTranscurridos = now()->diffInSeconds($intentoEnCurso->iniciado_en);
                    if ($segundosTranscurridos > $procesoPrueba->tiempo_maximo_seg) {
                        $intentoEnCurso->update([
                            'estado' => EstadoIntento::Expirado,
                            'finalizado_en' => now(),
                        ]);
                    } else {
                        $this->asegurarPreguntasAsignadas($intentoEnCurso, $procesoPrueba);
                        return $intentoEnCurso;
                    }
                } else {
                    $this->asegurarPreguntasAsignadas($intentoEnCurso, $procesoPrueba);
                    return $intentoEnCurso;
                }
            }

            // Verificar cantidad máxima de intentos
            $intentosRealizados = IntentoPrueba::where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $procesoPrueba->id)
                ->count();

            if ($procesoPrueba->max_intentos !== null && $intentosRealizados >= $procesoPrueba->max_intentos) {
                throw ValidationException::withMessages([
                    'intentos' => 'Ha alcanzado el límite máximo de intentos permitidos para esta evaluación.',
                ]);
            }

            $nuevoIntento = IntentoPrueba::create([
                'postulacion_id' => $postulacion->id,
                'proceso_prueba_id' => $procesoPrueba->id,
                'nro_intento' => $intentosRealizados + 1,
                'estado' => EstadoIntento::EnCurso,
                'iniciado_en' => now(),
            ]);

            $this->asegurarPreguntasAsignadas($nuevoIntento, $procesoPrueba);

            return $nuevoIntento;
        });
    }

    /**
     * Extrae y asigna las preguntas para el intento de evaluación.
     * Si la prueba tiene configurado un límite (ej. 20 de 200), extrae una muestra aleatoria
     * para que cada postulante rinda un examen único.
     */
    private function asegurarPreguntasAsignadas(IntentoPrueba $intento, ProcesoPrueba $procesoPrueba): void
    {
        $yaTienePreguntas = RespuestaIntento::where('intento_id', $intento->id)->exists();
        if ($yaTienePreguntas) {
            return;
        }

        $limite = $procesoPrueba->limite_preguntas ?? $procesoPrueba->prueba->limite_preguntas;
        $totalBanco = Pregunta::where('prueba_id', $procesoPrueba->prueba_id)->count();

        $query = Pregunta::where('prueba_id', $procesoPrueba->prueba_id);

        if ($limite && $limite > 0 && $limite < $totalBanco) {
            // Muestra aleatoria de N preguntas
            $preguntas = $query->inRandomOrder()->take($limite)->get();
        } else {
            // Todas las preguntas del banco en su orden correlativo
            $preguntas = $query->orderBy('orden')->get();
        }

        foreach ($preguntas as $p) {
            RespuestaIntento::create([
                'intento_id' => $intento->id,
                'pregunta_id' => $p->id,
                'opcion_id' => null,
                'respondida_en' => now(),
            ]);
        }
    }
}

