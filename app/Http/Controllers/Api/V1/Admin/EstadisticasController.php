<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class EstadisticasController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filtros = $request->validate(['dias' => ['sometimes', 'integer', 'in:7,30,90']]);
        $dias = (int) ($filtros['dias'] ?? 30);
        $hasta = CarbonImmutable::today(config('app.zona_horaria_negocio'));
        $desde = $hasta->subDays($dias - 1);

        $eventos = DB::table('talento.evento_acceso')
            ->where('created_at', '>=', $desde->toIso8601String())
            ->where('created_at', '<', $hasta->addDay()->toIso8601String());

        $actividad = $this->actividadDiaria(clone $eventos, $desde, $hasta);

        $paginas = $eventos->where('tipo', 'VISITA')
            ->select('ruta')
            ->selectRaw('COUNT(*) AS visitas')
            ->groupBy('ruta')
            ->orderByDesc('visitas')
            ->orderBy('ruta')
            ->limit(8)
            ->get()
            ->map(fn (stdClass $pagina): array => ['ruta' => $pagina->ruta, 'visitas' => (int) $pagina->visitas]);

        return response()->json(['data' => [
            'periodo' => ['dias' => $dias, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'totales' => [
                'visitas' => array_sum(array_column($actividad, 'visitas')),
                'accesos_exitosos' => array_sum(array_column($actividad, 'accesos_exitosos')),
                'accesos_fallidos' => array_sum(array_column($actividad, 'accesos_fallidos')),
            ],
            'actividad' => $actividad,
            'paginas' => $paginas,
            'postulaciones' => $this->postulacionesPorEtapa(),
        ]]);
    }

    /**
     * @return list<array{fecha: string, visitas: int, accesos_exitosos: int, accesos_fallidos: int}>
     */
    private function actividadDiaria(Builder $eventos, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $porFecha = $eventos->selectRaw('timezone(?, created_at)::date as fecha', [$desde->getTimezone()->getName()])
            ->selectRaw("COUNT(*) FILTER (WHERE tipo = 'VISITA') AS visitas")
            ->selectRaw("COUNT(*) FILTER (WHERE tipo = 'LOGIN_EXITOSO') AS accesos_exitosos")
            ->selectRaw("COUNT(*) FILTER (WHERE tipo = 'LOGIN_FALLIDO') AS accesos_fallidos")
            ->groupBy('fecha')
            ->get()
            ->keyBy('fecha');

        $actividad = [];

        for ($dia = $desde; $dia <= $hasta; $dia = $dia->addDay()) {
            $fecha = $dia->toDateString();
            $totales = $porFecha->get($fecha);
            $actividad[] = [
                'fecha' => $fecha,
                'visitas' => (int) ($totales->visitas ?? 0),
                'accesos_exitosos' => (int) ($totales->accesos_exitosos ?? 0),
                'accesos_fallidos' => (int) ($totales->accesos_fallidos ?? 0),
            ];
        }

        return $actividad;
    }

    /**
     * @return list<array{etapa: string, nombre: string, aprobadas: int, en_curso: int, reprobadas: int}>
     */
    private function postulacionesPorEtapa(): array
    {
        return DB::table('talento.v_embudo_proceso')
            ->select('etapa', 'etapa_nombre')
            ->selectRaw('SUM(aprobadas) AS aprobadas, SUM(en_curso) AS en_curso, SUM(reprobadas) AS reprobadas')
            ->groupBy('etapa', 'etapa_nombre')
            ->havingRaw('SUM(aprobadas + en_curso + reprobadas) > 0')
            ->orderByRaw('MIN(orden)')
            ->orderBy('etapa_nombre')
            ->get()
            ->map(fn (stdClass $etapa): array => [
                'etapa' => $etapa->etapa,
                'nombre' => $etapa->etapa_nombre,
                'aprobadas' => (int) $etapa->aprobadas,
                'en_curso' => (int) $etapa->en_curso,
                'reprobadas' => (int) $etapa->reprobadas,
            ])
            ->all();
    }
}
