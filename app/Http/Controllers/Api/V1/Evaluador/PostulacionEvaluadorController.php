<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostulacionEvaluadorController extends Controller
{
    /**
     * Lista todas las postulaciones de todos los procesos para el panel de evaluadores y administradores.
     */
    public function index(Request $request): JsonResponse
    {
        $consulta = Postulacion::with([
            'persona.usuario',
            'persona.egresados',
            'proceso.convocatoria.carrera.facultad',
            'proceso.solicitud',
            'etapas.procesoEtapa.etapa',
            'ranking',
        ])->orderByDesc('id');

        $usuario = $request->user();

        if ($usuario->rol?->alcance_carrera) {
            $carreraIds = $usuario->carreras()->pluck('carrera.id');

            if ($carreraIds->isNotEmpty()) {
                $consulta->where(function ($filtro) use ($carreraIds) {
                    $filtro->whereHas('proceso.convocatoria', fn ($convocatoria) => $convocatoria->whereIn('carrera_id', $carreraIds))
                        ->orWhereHas('proceso.solicitud', fn ($solicitud) => $solicitud->whereIn('carrera_id', $carreraIds));
                });
            }
        }

        $postulaciones = $consulta->get();

        $data = $postulaciones->map(function (Postulacion $p) {
            $convocatoria = $p->proceso?->convocatoria;
            $etapaActiva = $p->etapas
                ->sortBy('procesoEtapa.orden')
                ->first(fn ($e) => in_array($e->estado?->value, ['PENDIENTE', 'EN_CURSO', 'EN_REVISION'], true))
                ?? $p->etapas->sortByDesc('procesoEtapa.orden')->first();

            return [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'proceso_id' => $p->proceso_id,
                'proceso_codigo' => $p->proceso?->codigo,
                'estado' => $p->estado?->value,
                'postulante' => [
                    'nombre_completo' => $p->persona?->nombre_completo,
                    'ci' => $p->persona?->ci,
                    'correo' => $p->persona?->usuario?->correo,
                ],
                'carrera' => $convocatoria?->carrera?->nombre,
                'facultad' => $convocatoria?->carrera?->facultad?->nombre,
                'gestion' => $convocatoria?->gestion,
                'ppa' => (float) ($p->persona?->egresados?->first()?->ppa ?? 0),
                'etapa_actual' => [
                    'codigo' => $etapaActiva?->procesoEtapa?->etapa?->codigo,
                    'nombre' => $etapaActiva?->procesoEtapa?->etapa?->nombre,
                    'orden' => $etapaActiva?->procesoEtapa?->orden,
                    'estado' => $etapaActiva?->estado?->value,
                ],
                'ranking' => $p->ranking ? [
                    'posicion' => $p->ranking->posicion,
                    'puntaje_total' => (float) $p->ranking->puntaje_total,
                    'es_top' => (bool) $p->ranking->es_top,
                ] : null,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
