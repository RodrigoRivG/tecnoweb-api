<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\TipoProceso;
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
            'proceso.solicitud.contacto.empresa',
            'proceso.solicitud.carrera.facultad',
            'etapas.procesoEtapa.etapa',
            'resultados.procesoPrueba.procesoEtapa.etapa',
            'ranking',
        ])->orderByDesc('id');

        if ($request->filled('proceso_id')) {
            $consulta->where('proceso_id', (int) $request->query('proceso_id'));
        }

        $origenFiltro = $request->query('origen');
        if ($origenFiltro === 'empresa') {
            $consulta->whereHas('proceso', fn ($proceso) => $proceso->where('tipo', TipoProceso::Empresa));
        } elseif ($origenFiltro === 'talento') {
            $consulta->whereHas('proceso', fn ($proceso) => $proceso->where('tipo', '!=', TipoProceso::Empresa));
        }

        $postulaciones = $consulta->get();

        $data = $postulaciones->map(function (Postulacion $p) {
            $convocatoria = $p->proceso?->convocatoria;
            $solicitud = $p->proceso?->solicitud;
            $esEmpresa = $p->proceso?->tipo === TipoProceso::Empresa;
            $carrera = $convocatoria?->carrera ?? $solicitud?->carrera;

            $etapaActiva = $p->etapas
                ->sortBy('procesoEtapa.orden')
                ->first(fn ($e) => in_array($e->estado?->value, ['PENDIENTE', 'EN_CURSO', 'EN_REVISION'], true))
                ?? $p->etapas->sortByDesc('procesoEtapa.orden')->first();

            $etapaPsico = $p->etapas->first(
                fn ($e) => $e->procesoEtapa?->etapa?->codigo === 'EVALUACION_PSICOLOGICA'
            );
            $resultadosPsico = $p->resultados->filter(
                fn ($r) => $r->procesoPrueba?->procesoEtapa?->etapa?->codigo === 'EVALUACION_PSICOLOGICA'
            );
            $pruebasCompletas = $etapaPsico?->estado === EstadoEtapa::Aprobada;

            $situacion = match ($p->estado) {
                EstadoPostulacion::Aprobada => 'seleccionado',
                EstadoPostulacion::NoAprobada => 'rechazado',
                EstadoPostulacion::Retirada => 'retirado',
                default => match (true) {
                    $pruebasCompletas => 'pruebas_completas',
                    $resultadosPsico->isNotEmpty()
                        || in_array($etapaPsico?->estado, [EstadoEtapa::EnCurso, EstadoEtapa::Pendiente], true) => 'en_pruebas',
                    default => 'sin_pruebas',
                },
            };

            return [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'proceso_id' => $p->proceso_id,
                'proceso_codigo' => $p->proceso?->codigo,
                'origen' => $esEmpresa ? 'empresa' : 'talento',
                'estado' => $p->estado?->value,
                'postulante' => [
                    'nombre_completo' => $p->persona?->nombre_completo,
                    'ci' => $p->persona?->ci,
                    'correo' => $p->persona?->usuario?->correo,
                ],
                'carrera' => $carrera?->nombre,
                'facultad' => $carrera?->facultad?->nombre,
                'gestion' => $convocatoria?->gestion,
                'empresa' => $solicitud?->contacto?->empresa?->razon_social,
                'vacante' => $solicitud?->cargo,
                'ppa' => $esEmpresa ? null : (float) ($p->persona?->egresados?->first()?->ppa ?? 0),
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
                'resumen' => [
                    'situacion' => $situacion,
                    'etapa_psicologica' => $etapaPsico?->estado?->value,
                    'pruebas_psicometricas' => [
                        'con_resultado' => $resultadosPsico->count(),
                        'completas' => $pruebasCompletas,
                    ],
                    'embudo_cerrado' => in_array($p->estado, [EstadoPostulacion::Aprobada, EstadoPostulacion::NoAprobada], true),
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }
}
