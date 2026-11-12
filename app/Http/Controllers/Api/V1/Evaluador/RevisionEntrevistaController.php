<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Actions\Evaluacion\CalificarEntrevista;
use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use App\Models\ProcesoPrueba;
use App\Models\ResultadoPrueba;
use App\Models\RubricaCriterio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionEntrevistaController extends Controller
{
    /**
     * Retorna los datos de la postulación, los criterios de la rúbrica de entrevista y el resultado previo si existe.
     */
    public function formulario(Request $request, int $postulacionId): JsonResponse
    {
        $postulacion = Postulacion::with([
            'persona.usuario',
            'proceso.convocatoria.carrera.facultad',
            'etapas.procesoEtapa.etapa',
        ])->findOrFail($postulacionId);

        $convocatoria = $postulacion->proceso?->convocatoria;

        $etapaEntrevista = $postulacion->etapas
            ->first(fn ($e) => $e->procesoEtapa?->etapa?->codigo === 'ENTREVISTA');

        $procesoPrueba = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista?->proceso_etapa_id)
            ->whereHas('prueba', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
            ->with('prueba')
            ->first();

        $criterios = $procesoPrueba
            ? RubricaCriterio::where('prueba_id', $procesoPrueba->prueba_id)->orderBy('orden')->get()
            : collect();

        $resultado = $procesoPrueba
            ? ResultadoPrueba::where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $procesoPrueba->id)
                ->with('calificacionesCriterios.criterio')
                ->first()
            : null;

        return response()->json([
            'data' => [
                'postulacion' => [
                    'id' => $postulacion->id,
                    'codigo' => $postulacion->codigo,
                    'postulante' => [
                        'nombre_completo' => $postulacion->persona?->nombre_completo,
                        'ci' => $postulacion->persona?->ci,
                        'correo' => $postulacion->persona?->usuario?->correo,
                    ],
                    'convocatoria' => [
                        'titulo' => $convocatoria?->titulo,
                        'carrera' => $convocatoria?->carrera?->nombre,
                        'facultad' => $convocatoria?->carrera?->facultad?->nombre,
                    ],
                    'etapa_entrevista' => [
                        'estado' => $etapaEntrevista?->estado?->value,
                        'orden' => $etapaEntrevista?->procesoEtapa?->orden,
                        'finalizada_en' => $etapaEntrevista?->finalizada_en,
                    ],
                ],
                'criterios' => $criterios->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'puntaje_maximo' => (float) $c->puntaje_maximo,
                    'orden' => $c->orden,
                ]),
                'resultado' => $resultado ? [
                    'puntaje_bruto' => (float) $resultado->puntaje_bruto,
                    'puntaje_maximo' => (float) $resultado->puntaje_maximo,
                    'puntaje_normalizado' => (float) $resultado->puntaje_normalizado,
                    'aprobada' => $resultado->aprobada,
                    'observaciones' => $resultado->observaciones,
                    'calificado_en' => $resultado->calificado_en,
                    'calificaciones' => $resultado->calificacionesCriterios->map(fn ($cc) => [
                        'criterio_id' => $cc->criterio_id,
                        'nombre_criterio' => $cc->criterio?->nombre,
                        'puntaje' => (float) $cc->puntaje,
                        'comentario' => $cc->comentario,
                    ]),
                ] : null,
            ],
        ]);
    }

    /**
     * Guarda la evaluación de la rúbrica de Entrevista y actualiza el avance de la postulación.
     */
    public function calificar(Request $request, int $postulacionId, CalificarEntrevista $calificar): JsonResponse
    {
        $datos = $request->validate([
            'calificaciones' => ['required', 'array', 'min:1'],
            'calificaciones.*.criterio_id' => ['required', 'integer'],
            'calificaciones.*.puntaje' => ['required', 'numeric', 'min:0'],
            'calificaciones.*.comentario' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $postulacion = Postulacion::findOrFail($postulacionId);

        $resultado = $calificar->handle($request->user(), $postulacion, $datos);

        return response()->json([
            'mensaje' => 'Evaluación de Entrevista registrada exitosamente.',
            'data' => [
                'puntaje_bruto' => (float) $resultado->puntaje_bruto,
                'puntaje_maximo' => (float) $resultado->puntaje_maximo,
                'puntaje_normalizado' => (float) $resultado->puntaje_normalizado,
                'aprobada' => $resultado->aprobada,
                'calificado_en' => $resultado->calificado_en,
            ],
        ]);
    }
}

