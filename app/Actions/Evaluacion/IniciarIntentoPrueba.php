<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoIntento;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
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

        app(AsignarInstrumentosPostulacion::class)->handle($postulacion);

        $esBateria = $procesoPrueba->prueba?->tipo === TipoPrueba::Test && $procesoPrueba->prueba->familia !== null;
        $asignada = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->exists();
        $yaEmpezo = IntentoPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->exists();

        if ($esBateria && ! $asignada && ! $yaEmpezo) {
            throw ValidationException::withMessages([
                'prueba' => 'Esta prueba no está asignada. Cada familia tiene un solo libro.',
            ]);
        }

        return Auditoria::comoUsuario($usuario->id, function () use ($postulacion, $procesoPrueba): IntentoPrueba {
            // Verificar si ya existe un intento en curso
            $intentoEnCurso = IntentoPrueba::where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $procesoPrueba->id)
                ->where('estado', EstadoIntento::EnCurso)
                ->first();

            if ($intentoEnCurso) {
                $this->asegurarPreguntasAsignadas($intentoEnCurso, $procesoPrueba);

                return $intentoEnCurso;
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
     * Copia todas las preguntas del libro asignado, en orden. No recorta el banco.
     */
    private function asegurarPreguntasAsignadas(IntentoPrueba $intento, ProcesoPrueba $procesoPrueba): void
    {
        $yaTienePreguntas = RespuestaIntento::where('intento_id', $intento->id)->exists();
        if ($yaTienePreguntas) {
            return;
        }

        $pruebaId = AsignacionPrueba::query()
            ->where('postulacion_id', $intento->postulacion_id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->value('prueba_id') ?? $procesoPrueba->prueba_id;

        $preguntas = Pregunta::where('prueba_id', $pruebaId)->orderBy('orden')->get();

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
