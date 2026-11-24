<?php

namespace App\Actions\Informe;

use App\Enums\FamiliaPrueba;
use App\Models\AsignacionPrueba;
use App\Models\DimensionEvaluada;
use App\Models\Postulacion;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\ResultadoPrueba;
use App\Models\RubricaCriterio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArmarInformePostulacion
{
    /** @var list<string> */
    private const PRUEBAS_INTELIGENCIA = ['TEST_INTELIGENCIA', 'DOMINOS_D48', 'ES_USTED_MUY_HABIL', 'RAVEN'];

    /** @var list<string> */
    private const PRUEBAS_ESPECIFICAS = [
        'TEST_LECTURA_COMPRENSIVA',
        'TEST_PURDUE_NO_VERBAL',
        'TEST_APTITUD_MECANICA',
        'TEST_CODIFICACION',
        'TEST_LABERINTOS_VISOESPACIAL',
    ];

    /**
     * Arma el informe consolidado. La compatibilidad de una curva es la cercanía
     * media entre esten real e ideal (distancia 0 = 100, distancia 9 = 0).
     * El porcentaje global promedia esas curvas con los puntajes normalizados
     * de inteligencia, aptitudes específicas y entrevista.
     *
     * @return array<string, mixed>
     */
    public function handle(Postulacion $postulacion): array
    {
        $postulacion->loadMissing(['persona', 'proceso']);

        $resultados = ResultadoPrueba::query()
            ->with(['procesoPrueba.prueba', 'dimensiones', 'calificacionesCriterios.criterio'])
            ->where('postulacion_id', $postulacion->id)
            ->get();

        $ideales = DB::table('talento.perfil_requerido')
            ->where('proceso_id', $postulacion->proceso_id)
            ->pluck('valor_ideal', 'dimension_id');

        $reales = $this->realesPorDimension($resultados);
        $personalidad = $this->perfilPersonalidad($ideales, $reales);
        $blandas = $this->perfilHabilidadesBlandas($ideales, $reales);
        $resumen = $this->resumenPuntajes($postulacion, $resultados);

        $aproximacionPersonalidad = $this->promedio(collect($personalidad)->pluck('porcentaje_aproximacion'));
        $aproximacionBlandas = $this->promedio(collect($blandas)->pluck('porcentaje_aproximacion'));

        $muestras = collect([
            $aproximacionPersonalidad,
            $aproximacionBlandas,
            $resumen['inteligencia']['porcentaje'],
            $resumen['subtotal_habilidades_especificas']['porcentaje'],
            $resumen['entrevista']['porcentaje'],
        ])->filter(fn ($valor) => $valor !== null);

        return [
            'postulacion_id' => $postulacion->id,
            'codigo' => $postulacion->codigo,
            'postulante' => $postulacion->persona?->nombre_completo,
            'proceso' => $postulacion->proceso?->nombre,
            'porcentaje_compatibilidad_global' => $this->promedio($muestras),
            'porcentaje_aproximacion_personalidad' => $aproximacionPersonalidad,
            'porcentaje_aproximacion_habilidades' => $aproximacionBlandas,
            'perfil_personalidad' => $personalidad,
            'perfil_habilidades_blandas' => $blandas,
            'resumen_puntajes' => $resumen,
        ];
    }

    /**
     * @param  Collection<int|string, mixed>  $ideales
     * @param  array<int, array{esten: float, bruto: ?float}>  $reales
     * @return list<array<string, mixed>>
     */
    private function perfilPersonalidad(Collection $ideales, array $reales): array
    {
        return DimensionEvaluada::query()
            ->where('tipo', 'PERSONALIDAD')
            ->whereNotNull('factor')
            ->orderBy('orden')
            ->get()
            ->map(function (DimensionEvaluada $dimension) use ($ideales, $reales): array {
                $real = $reales[$dimension->id] ?? null;
                $ideal = $ideales->has($dimension->id) ? (float) $ideales[$dimension->id] : null;
                $estenReal = $real['esten'] ?? null;

                return [
                    'dimension_id' => $dimension->id,
                    'codigo' => $dimension->factor,
                    'nombre' => $dimension->nombre,
                    'polo_bajo' => $dimension->polo_bajo,
                    'polo_alto' => $dimension->polo_alto,
                    'bruto' => $real['bruto'] ?? null,
                    'esten_real' => $estenReal,
                    'esten_ideal' => $ideal,
                    'porcentaje_aproximacion' => $this->aproximacion($estenReal, $ideal),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int|string, mixed>  $ideales
     * @param  array<int, array{esten: float, bruto: ?float}>  $reales
     * @return list<array<string, mixed>>
     */
    private function perfilHabilidadesBlandas(Collection $ideales, array $reales): array
    {
        return DimensionEvaluada::query()
            ->where('tipo', 'HABILIDAD_BLANDA')
            ->orderBy('orden')
            ->get()
            ->map(function (DimensionEvaluada $dimension) use ($ideales, $reales): array {
                $real = $reales[$dimension->id] ?? null;
                $ideal = $ideales->has($dimension->id) ? (float) $ideales[$dimension->id] : null;
                $valorReal = $real['esten'] ?? null;

                return [
                    'dimension_id' => $dimension->id,
                    'codigo' => $dimension->codigo,
                    'nombre' => $dimension->nombre,
                    'polo_bajo' => 'Bajo',
                    'polo_alto' => 'Alto',
                    'valor_real' => $valorReal,
                    'valor_ideal' => $ideal,
                    'porcentaje_aproximacion' => $this->aproximacion($valorReal, $ideal),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @return array<string, mixed>
     */
    private function resumenPuntajes(Postulacion $postulacion, Collection $resultados): array
    {
        $inteligencia = $resultados->first(fn (ResultadoPrueba $resultado) => $this->instrumento($resultado)?->familia === FamiliaPrueba::Inteligencia
            || in_array($this->instrumento($resultado)?->codigo, self::PRUEBAS_INTELIGENCIA, true));
        $especificas = $resultados
            ->filter(fn (ResultadoPrueba $resultado) => $this->instrumento($resultado)?->familia === FamiliaPrueba::HabilidadEspecifica
                || in_array($this->instrumento($resultado)?->codigo, self::PRUEBAS_ESPECIFICAS, true))
            ->map(fn (ResultadoPrueba $resultado) => $this->puntajePrueba($resultado))
            ->values();

        $entrevista = $resultados->first(fn (ResultadoPrueba $resultado) => $resultado->procesoPrueba?->prueba?->codigo === 'ENTREVISTA');

        $total = DB::table('talento.v_puntaje_postulacion')
            ->where('postulacion_id', $postulacion->id)
            ->value('puntaje_total');

        return [
            'inteligencia' => $inteligencia
                ? $this->puntajePrueba($inteligencia)
                : ['codigo' => null, 'nombre' => null, 'puntaje' => null, 'maximo' => 100, 'porcentaje' => null],
            'habilidades_especificas' => $especificas->all(),
            'subtotal_habilidades_especificas' => [
                'puntaje' => $this->promedio($especificas->pluck('porcentaje')),
                'maximo' => 100,
                'porcentaje' => $this->promedio($especificas->pluck('porcentaje')),
            ],
            'entrevista' => $this->entrevista($postulacion, $entrevista),
            'total_general' => [
                'puntaje' => $total !== null ? round((float) $total, 2) : null,
                'maximo' => 100,
            ],
        ];
    }

    /**
     * @return array{codigo: ?string, nombre: ?string, puntaje: ?float, maximo: int, porcentaje: ?float}
     */
    private function puntajePrueba(ResultadoPrueba $resultado): array
    {
        $prueba = $this->instrumento($resultado);
        $porcentaje = $resultado->puntaje_normalizado !== null ? round((float) $resultado->puntaje_normalizado, 2) : null;

        return [
            'codigo' => $prueba?->codigo,
            'nombre' => $prueba?->nombre,
            'puntaje' => $porcentaje,
            'maximo' => 100,
            'porcentaje' => $porcentaje,
        ];
    }

    /**
     * @return array{total: ?float, maximo: ?float, porcentaje: ?float, criterios: list<array{nombre: string, puntaje: ?float, maximo: float}>}
     */
    private function entrevista(Postulacion $postulacion, ?ResultadoPrueba $resultado): array
    {
        $procesoPrueba = ProcesoPrueba::query()
            ->whereHas('procesoEtapa', fn ($consulta) => $consulta->where('proceso_id', $postulacion->proceso_id))
            ->whereHas('prueba', fn ($consulta) => $consulta->where('codigo', 'ENTREVISTA'))
            ->first();

        $criterios = $procesoPrueba
            ? RubricaCriterio::query()->where('prueba_id', $procesoPrueba->prueba_id)->orderBy('orden')->get()
            : collect();

        $notas = $resultado?->calificacionesCriterios->keyBy('criterio_id') ?? collect();

        return [
            'total' => $resultado ? round((float) $resultado->puntaje_bruto, 2) : null,
            'maximo' => $resultado
                ? round((float) $resultado->puntaje_maximo, 2)
                : ($criterios->isEmpty() ? null : round((float) $criterios->sum('puntaje_maximo'), 2)),
            'porcentaje' => $resultado && $resultado->puntaje_normalizado !== null
                ? round((float) $resultado->puntaje_normalizado, 2)
                : null,
            'criterios' => $criterios->map(function (RubricaCriterio $criterio) use ($notas): array {
                $nota = $notas->get($criterio->id);

                return [
                    'nombre' => $criterio->nombre,
                    'puntaje' => $nota ? round((float) $nota->puntaje, 2) : null,
                    'maximo' => round((float) $criterio->puntaje_maximo, 2),
                ];
            })->all(),
        ];
    }

    /**
     * El último resultado calificado gana si una dimensión se midió más de una vez.
     *
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @return array<int, array{esten: float, bruto: ?float}>
     */
    private function realesPorDimension(Collection $resultados): array
    {
        $intentoIds = $resultados->pluck('intento_id')->filter()->values();
        $brutos = [];

        if ($intentoIds->isNotEmpty()) {
            $filas = DB::table('talento.respuesta_intento as r')
                ->join('talento.opcion_pregunta as o', 'r.opcion_id', '=', 'o.id')
                ->join('talento.pregunta as p', 'r.pregunta_id', '=', 'p.id')
                ->whereIn('r.intento_id', $intentoIds)
                ->whereNotNull('p.dimension_id')
                ->groupBy('r.intento_id', 'p.dimension_id')
                ->selectRaw('r.intento_id, p.dimension_id, SUM(o.puntaje) as bruto')
                ->get();

            foreach ($filas as $fila) {
                $brutos[$fila->intento_id][$fila->dimension_id] = round((float) $fila->bruto, 2);
            }
        }

        $reales = [];
        foreach ($resultados->sortBy('calificado_en') as $resultado) {
            foreach ($resultado->dimensiones as $dimension) {
                $brutoGuardado = $dimension->puntuacion_bruta !== null ? round((float) $dimension->puntuacion_bruta, 2) : null;
                $reales[$dimension->dimension_id] = [
                    'esten' => round((float) $dimension->valor, 2),
                    'bruto' => $brutoGuardado ?? ($brutos[$resultado->intento_id][$dimension->dimension_id] ?? null),
                ];
            }
        }

        return $reales;
    }

    private function instrumento(ResultadoPrueba $resultado): ?Prueba
    {
        $asignada = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $resultado->postulacion_id)
            ->where('proceso_prueba_id', $resultado->proceso_prueba_id)
            ->first();

        return $asignada?->prueba ?? $resultado->procesoPrueba?->prueba;
    }

    private function aproximacion(?float $real, ?float $ideal): ?float
    {
        if ($real === null || $ideal === null) {
            return null;
        }

        $distancia = abs($this->acotar($real) - $this->acotar($ideal));

        return round((1 - ($distancia / 9)) * 100, 2);
    }

    private function acotar(float $valor): float
    {
        return max(1, min(10, $valor));
    }

    /**
     * @param  Collection<int, mixed>  $valores
     */
    private function promedio(Collection $valores): ?float
    {
        $validos = $valores->filter(fn ($valor) => $valor !== null)->map(fn ($valor) => (float) $valor);
        if ($validos->isEmpty()) {
            return null;
        }

        return round($validos->avg(), 2);
    }
}
