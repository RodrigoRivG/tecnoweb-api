<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoIntento;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\RespuestaIntento;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\TiempoPregunta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarRespuestaIntento
{
    public function handle(Usuario $usuario, IntentoPrueba $intento, int $preguntaId, int $opcionId): RespuestaIntento
    {
        if ($intento->postulacion->persona_id !== $usuario->persona?->id) {
            throw ValidationException::withMessages([
                'intento' => 'No tiene autorización sobre este intento de evaluación.',
            ]);
        }

        if ($intento->estado !== EstadoIntento::EnCurso) {
            throw ValidationException::withMessages([
                'intento' => 'El intento no se encuentra en curso.',
            ]);
        }

        $procesoPrueba = $intento->procesoPrueba;
        $pruebaAsignada = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $intento->postulacion_id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->first()
            ?->prueba ?? $procesoPrueba->prueba;
        $limitePregunta = TiempoPregunta::segundos($pruebaAsignada?->familia);

        $pruebaId = $pruebaAsignada?->id ?? $procesoPrueba->prueba_id;

        $pregunta = Pregunta::where('id', $preguntaId)
            ->where('prueba_id', $pruebaId)
            ->first();

        if (! $pregunta) {
            throw ValidationException::withMessages([
                'pregunta_id' => 'La pregunta especificada no pertenece a esta evaluación.',
            ]);
        }

        // Si el intento tiene asignado un lote aleatorio de preguntas, validar que pertenezca al intento
        if ($intento->respuestas()->exists() && ! $intento->respuestas()->where('pregunta_id', $preguntaId)->exists()) {
            throw ValidationException::withMessages([
                'pregunta_id' => 'La pregunta no forma parte de este examen.',
            ]);
        }

        $opcion = OpcionPregunta::where('id', $opcionId)
            ->where('pregunta_id', $pregunta->id)
            ->first();

        if (! $opcion) {
            throw ValidationException::withMessages([
                'opcion_id' => 'La opción especificada no es válida para la pregunta indicada.',
            ]);
        }

        return Auditoria::comoUsuario($usuario->id, function () use ($intento, $pregunta, $opcion, $limitePregunta): RespuestaIntento {
            $fila = DB::table('talento.respuesta_intento')
                ->where('intento_id', $intento->id)
                ->where('pregunta_id', $pregunta->id)
                ->first();

            if ($fila !== null && filter_var($fila->expirada, FILTER_VALIDATE_BOOLEAN)) {
                return RespuestaIntento::where('intento_id', $intento->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->firstOrFail();
            }

            if ($fila !== null && $fila->opcion_id !== null) {
                throw ValidationException::withMessages([
                    'opcion_id' => 'La respuesta ya quedó registrada y no se puede modificar.',
                ]);
            }

            if (TiempoPregunta::vencido($intento->pregunta_actual_iniciada_en, $limitePregunta, true)) {
                DB::table('talento.respuesta_intento')
                    ->where('intento_id', $intento->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->update([
                        'expirada' => true,
                        'respondida_en' => now(),
                        'updated_at' => now(),
                    ]);
                $intento->update(['pregunta_actual_iniciada_en' => null]);

                return RespuestaIntento::where('intento_id', $intento->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->firstOrFail();
            }

            if ($fila !== null) {
                DB::table('talento.respuesta_intento')
                    ->where('intento_id', $intento->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->update([
                        'opcion_id' => $opcion->id,
                        'respondida_en' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('talento.respuesta_intento')->insert([
                    'intento_id' => $intento->id,
                    'pregunta_id' => $pregunta->id,
                    'opcion_id' => $opcion->id,
                    'expirada' => false,
                    'respondida_en' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $intento->update(['pregunta_actual_iniciada_en' => null]);

            return RespuestaIntento::where('intento_id', $intento->id)
                ->where('pregunta_id', $pregunta->id)
                ->firstOrFail();
        });
    }
}

