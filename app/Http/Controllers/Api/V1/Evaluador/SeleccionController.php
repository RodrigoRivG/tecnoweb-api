<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Actions\Evaluacion\AdjudicarSeleccion;
use App\Actions\Evaluacion\CalcularRankingSeleccion;
use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\RankingResultado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeleccionController extends Controller
{
    /**
     * Retorna el cuadro de honor y ranking ponderado del proceso.
     */
    public function ranking(Request $request, int $procesoId, CalcularRankingSeleccion $calcular): JsonResponse
    {
        $proceso = Proceso::with(['convocatoria.carrera.facultad', 'etapas.etapa'])->findOrFail($procesoId);

        $rankingItems = $calcular->handle($proceso);

        $registroPublicacion = RankingResultado::where('proceso_id', $proceso->id)->first();

        return response()->json([
            'data' => [
                'proceso' => [
                    'id' => $proceso->id,
                    'codigo' => $proceso->codigo,
                    'nombre' => $proceso->nombre,
                    'top_n' => $proceso->top_n,
                    'carrera' => $proceso->convocatoria?->carrera?->nombre,
                    'facultad' => $proceso->convocatoria?->carrera?->facultad?->nombre,
                    'gestion' => $proceso->convocatoria?->gestion,
                ],
                'publicado' => (bool) ($registroPublicacion?->visible ?? false),
                'publicado_en' => $registroPublicacion?->publicado_en,
                'candidatos' => $rankingItems,
            ],
        ]);
    }

    /**
     * Adjudica las plazas del proceso y publica el ranking oficial definitivo.
     */
    public function adjudicar(Request $request, int $procesoId, AdjudicarSeleccion $adjudicar): JsonResponse
    {
        $datos = $request->validate([
            'postulaciones_adjudicadas' => ['nullable', 'array'],
            'postulaciones_adjudicadas.*' => ['integer'],
        ]);

        $proceso = Proceso::findOrFail($procesoId);

        $resultado = $adjudicar->handle(
            $request->user(),
            $proceso,
            $datos['postulaciones_adjudicadas'] ?? null
        );

        return response()->json([
            'mensaje' => 'Selección y adjudicación final procesada y publicada exitosamente.',
            'data' => $resultado,
        ]);
    }

    /**
     * Retorna el resultado individual final y acreditación de adjudicación para el postulante.
     */
    public function resultadoPostulante(Request $request, int $postulacionId, CalcularRankingSeleccion $calcular): JsonResponse
    {
        $postulacion = Postulacion::with([
            'persona.usuario',
            'proceso.convocatoria.carrera.facultad',
            'ranking',
            'etapas.procesoEtapa.etapa',
        ])->findOrFail($postulacionId);

        $usuario = $request->user();
        $esDueno = $postulacion->persona?->usuario_id === $usuario->id;

        if (! $esDueno && ! $usuario->tienePermiso('postulaciones.ver')) {
            abort(403, 'No tienes permiso para ver el resultado de esta postulación.');
        }

        if (! $esDueno && ! $usuario->abarcaCarrera($postulacion->carreraId())) {
            abort(404);
        }

        // Si no tiene ranking calculado, ejecutar el cálculo
        if (! $postulacion->ranking) {
            $calcular->handle($postulacion->proceso);
            $postulacion->load('ranking');
        }

        $ranking = $postulacion->ranking;
        $etapaSeleccion = $postulacion->etapas->first(fn ($e) => $e->procesoEtapa?->etapa?->codigo === 'SELECCION');

        return response()->json([
            'data' => [
                'postulacion_id' => $postulacion->id,
                'codigo' => $postulacion->codigo,
                'estado' => $postulacion->estado?->value,
                'posicion' => $ranking?->posicion,
                'puntaje_total' => (float) ($ranking?->puntaje_total ?? 0),
                'es_top' => (bool) ($ranking?->es_top ?? false),
                'visible' => (bool) ($ranking?->visible ?? false),
                'publicado_en' => $ranking?->publicado_en,
                'seleccion_estado' => $etapaSeleccion?->estado?->value,
                'es_adjudicado' => $etapaSeleccion?->estado?->value === 'APROBADA',
            ],
        ]);
    }
}

