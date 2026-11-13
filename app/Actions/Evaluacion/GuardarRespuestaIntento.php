<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoIntento;
use App\Models\IntentoPrueba;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\RespuestaIntento;
use App\Models\Usuario;
use App\Support\Auditoria;
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
        if ($procesoPrueba->tiempo_maximo_seg !== null) {
            $segundosTranscurridos = now()->diffInSeconds($intento->iniciado_en);
            if ($segundosTranscurridos > $procesoPrueba->tiempo_maximo_seg) {
                $intento->update([
                    'estado' => EstadoIntento::Expirado,
                    'finalizado_en' => now(),
                ]);

                throw ValidationException::withMessages([
                    'tiempo' => 'El tiempo límite asignado para la evaluación ha expirado.',
                ]);
            }
        }

        $pregunta = Pregunta::where('id', $preguntaId)
            ->where('prueba_id', $procesoPrueba->prueba_id)
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

        return Auditoria::comoUsuario($usuario->id, function () use ($intento, $pregunta, $opcion): RespuestaIntento {
            $existe = DB::table('talento.respuesta_intento')
                ->where('intento_id', $intento->id)
                ->where('pregunta_id', $pregunta->id)
                ->exists();

            if ($existe) {
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
                    'respondida_en' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return RespuestaIntento::where('intento_id', $intento->id)
                ->where('pregunta_id', $pregunta->id)
                ->firstOrFail();
        });
    }
}

