<?php

namespace App\Actions\Informe;

use App\Enums\EstadoIntento;
use App\Enums\FamiliaPrueba;
use App\Models\AsignacionPrueba;
use App\Models\DimensionEvaluada;
use App\Models\IntentoPrueba;
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
    private const PRUEBAS_INTELIGENCIA = ['TEST_INTELIGENCIA', 'DOMINOS_D48', 'ES_USTED_MUY_HABIL', 'RAVEN', 'TEST_PURDUE_NO_VERBAL', 'TEST_PURDUE_NO_VERBAL_FORMA_B'];

    /** @var list<string> */
    private const PRUEBAS_ESPECIFICAS = [
        'TEST_LECTURA_COMPRENSIVA',
        'TEST_LECTURA_COMPRENSIVA_1',
        'TEST_APTITUD_MECANICA',
        'TEST_CODIFICACION',
        'TEST_LABERINTOS_VISOESPACIAL',
    ];

    /**
     * Por familia HABILIDAD_BLANDA cada postulante rinde un solo instrumento (rotación).
     *
     * @var array<string, list<string>>
     */
    /** @var list<string> */
    private const DIMENSIONES_HABILIDADES_UNIFICADAS = [
        'ATENCION_EMOCIONAL',
        'CLARIDAD_EMOCIONAL',
        'REPARACION_EMOCIONAL',
        'COMUNICACION_ASERTIVA',
        'TRABAJO_EN_EQUIPO',
        'RESOLUCION_CONFLICTOS',
        'ADAPTABILIDAD',
        'LIDERAZGO_INICIATIVA',
    ];

    /**
     * Legacy: postulaciones antiguas solo con TMMS-24 como libro aparte.
     *
     * @var array<string, list<string>>
     */
    private const DIMENSIONES_POR_INSTRUMENTO_BLANDA = [
        'TMMS_24' => ['ATENCION_EMOCIONAL', 'CLARIDAD_EMOCIONAL', 'REPARACION_EMOCIONAL'],
        'TEST_HABILIDADES_BLANDAS' => self::DIMENSIONES_HABILIDADES_UNIFICADAS,
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
        $postulacion->loadMissing(['persona', 'proceso.solicitud']);

        $resultados = ResultadoPrueba::query()
            ->with(['procesoPrueba.prueba', 'dimensiones', 'calificacionesCriterios.criterio'])
            ->where('postulacion_id', $postulacion->id)
            ->get();

        $ideales = DB::table('talento.perfil_requerido')
            ->where('proceso_id', $postulacion->proceso_id)
            ->pluck('valor_ideal', 'dimension_id');

        $reales = $this->realesPorDimension($resultados);
        $personalidad = $this->perfilPersonalidad($ideales, $reales);
        $blandasTodas = $this->perfilHabilidadesBlandas($ideales, $reales);
        $instrumentoBlandas = $this->instrumentoHabilidadesBlandas($postulacion, $resultados);
        $blandas = $this->filtrarPerfilBlandasPorInstrumento($blandasTodas, $instrumentoBlandas);
        $resumen = $this->resumenPuntajes($postulacion, $resultados);
        $completitud = $this->evaluarCompletitud($postulacion, $resultados, $personalidad, $blandasTodas, $instrumentoBlandas);

        $aproximacionPersonalidad = $completitud['personalidad_16pf_completa']
            ? $this->promedio(collect($personalidad)->pluck('porcentaje_aproximacion'))
            : null;
        $aproximacionBlandas = $completitud['habilidades_blandas_completa']
            ? $this->promedio(collect($blandas)->pluck('porcentaje_aproximacion'))
            : null;

        $muestras = collect([
            $aproximacionPersonalidad,
            $aproximacionBlandas,
            $completitud['inteligencia_completa'] ? $resumen['inteligencia']['porcentaje'] : null,
            $completitud['especificas_completas'] ? $resumen['subtotal_habilidades_especificas']['porcentaje'] : null,
            $completitud['entrevista_completa'] ? $resumen['entrevista']['porcentaje'] : null,
        ])->filter(fn ($valor) => $valor !== null);

        $ejecutivo = $this->informeEjecutivo($postulacion, $resultados, $resumen, $personalidad);

        return [
            'postulacion_id' => $postulacion->id,
            'codigo' => $postulacion->codigo,
            'postulante' => $postulacion->persona?->nombre_completo,
            'proceso' => $postulacion->proceso?->nombre,
            'porcentaje_compatibilidad_global' => $muestras->isEmpty() ? null : $this->promedio($muestras),
            'porcentaje_aproximacion_personalidad' => $aproximacionPersonalidad,
            'porcentaje_aproximacion_habilidades' => $aproximacionBlandas,
            'perfil_personalidad' => $personalidad,
            'perfil_habilidades_blandas' => $blandas,
            'instrumento_habilidades_blandas' => $instrumentoBlandas,
            'resumen_puntajes' => $resumen,
            'completitud' => $completitud,
            'informe_ejecutivo' => $ejecutivo,
        ];
    }

    /**
     * Ficha tipo presentación: datos del candidato y tabla coeficiente × bruto × ponderado.
     *
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @param  array<string, mixed>  $resumen
     * @return array<string, mixed>
     */
    /**
     * @param  list<array<string, mixed>>  $personalidad
     * @return array<string, mixed>
     */
    private function informeEjecutivo(Postulacion $postulacion, Collection $resultados, array $resumen, array $personalidad): array
    {
        $persona = $postulacion->persona;
        $proceso = $postulacion->proceso;
        $solicitud = $proceso?->solicitud;
        $cargo = trim((string) ($solicitud?->cargo ?? '')) ?: ($proceso?->nombre ?? '—');

        $edad = null;
        if ($persona?->fecha_nacimiento) {
            $edad = $persona->fecha_nacimiento->age;
        }

        $proceso?->loadMissing([
            'etapas' => fn ($consulta) => $consulta->where('activa', true)->orderBy('orden'),
            'etapas.pruebas' => fn ($consulta) => $consulta->where('peso', '>', 0)->with('prueba')->orderBy('orden'),
        ]);

        $pruebasConPeso = $proceso?->etapas->flatMap(fn ($etapa) => $etapa->pruebas) ?? collect();
        $filas = [];
        $subtotalPruebas = 0.0;

        foreach ($pruebasConPeso as $procesoPrueba) {
            $codigo = $procesoPrueba->prueba?->codigo;
            $resultado = $resultados->firstWhere('proceso_prueba_id', $procesoPrueba->id);
            $bruto = $this->puntajeBrutoInforme($codigo, $resultado);
            $peso = (float) $procesoPrueba->peso;
            $ponderado = $bruto !== null ? round($bruto * ($peso / 100), 2) : null;

            if ($ponderado !== null) {
                $subtotalPruebas += $ponderado;
            }

            $fila = [
                'etiqueta' => $this->etiquetaEvaluacion($codigo, $procesoPrueba->prueba?->nombre),
                'codigo' => $codigo,
                'coeficiente' => round($peso / 100, 2),
                'peso' => $peso,
                'puntaje_bruto' => $bruto,
                'puntaje_ponderado' => $ponderado,
            ];

            if ($codigo === 'ENTREVISTA' && $resultado !== null) {
                $fila['entrevista_puntos'] = [
                    'obtenido' => $resultado->puntaje_bruto !== null ? round((float) $resultado->puntaje_bruto, 2) : null,
                    'maximo' => $resultado->puntaje_maximo !== null ? round((float) $resultado->puntaje_maximo, 2) : null,
                ];
            }

            $filas[] = $fila;
        }

        $ent = $resumen['entrevista'];
        $entrevistaNorm = $ent['porcentaje'];
        if ($entrevistaNorm === null && $ent['total'] !== null && ($ent['maximo'] ?? 0) > 0) {
            $entrevistaNorm = round(((float) $ent['total'] / (float) $ent['maximo']) * 100, 2);
        }

        $total = $resumen['total_general']['puntaje'] ?? null;
        if ($total === null && $subtotalPruebas > 0) {
            $total = round($subtotalPruebas, 2);
        }

        return [
            'ficha' => [
                'nombre' => $persona?->nombre_completo,
                'cargo' => $cargo,
                'edad' => $edad,
                'sexo' => null,
                'estado_civil' => null,
                'codigo' => $postulacion->codigo,
            ],
            'tabla_ponderada' => $filas,
            'totales' => [
                'subtotal_pruebas' => round($subtotalPruebas, 2),
                'entrevista' => $entrevistaNorm !== null ? round((float) $entrevistaNorm, 2) : null,
                'entrevista_bruto' => $ent['total'] !== null ? round((float) $ent['total'], 2) : null,
                'entrevista_maximo' => $ent['maximo'] !== null ? round((float) $ent['maximo'], 2) : null,
                'puntaje_total' => $total,
            ],
        ];
    }

    private function puntajeBrutoInforme(?string $codigo, ?ResultadoPrueba $resultado): ?float
    {
        if ($resultado === null) {
            return null;
        }

        if ($codigo === 'ENTREVISTA') {
            if ($resultado->puntaje_normalizado !== null) {
                return round((float) $resultado->puntaje_normalizado, 2);
            }
            $maximo = (float) ($resultado->puntaje_maximo ?? 0);
            if ($maximo > 0 && $resultado->puntaje_bruto !== null) {
                return round(((float) $resultado->puntaje_bruto / $maximo) * 100, 2);
            }

            return null;
        }

        return $resultado->puntaje_normalizado !== null
            ? round((float) $resultado->puntaje_normalizado, 2)
            : null;
    }

    private function etiquetaEvaluacion(?string $codigo, ?string $nombre): string
    {
        return match ($codigo) {
            'CV_ANALISIS' => 'Evaluación curricular',
            '16PF_FORMA_A', '16PF_FORMA_B' => 'Personalidad',
            'ENTREVISTA' => 'Entrevista',
            'TMMS_24' => 'Inteligencia emocional',
            'ES_USTED_MUY_HABIL', 'TEST_INTELIGENCIA', 'DOMINOS_D48', 'RAVEN',
            'TEST_PURDUE_NO_VERBAL', 'TEST_PURDUE_NO_VERBAL_FORMA_B' => 'Inteligencia general',
            'TEST_LECTURA_COMPRENSIVA', 'TEST_LECTURA_COMPRENSIVA_1' => 'Lectura comprensiva',
            'TEST_CODIFICACION', 'TEST_APTITUD_NUMERICA' => 'Aptitud numérica',
            'TEST_APTITUD_MECANICA' => 'Prueba técnica',
            'PPA' => 'Promedio académico (PPA)',
            default => $nombre ?? $codigo ?? 'Evaluación',
        };
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @param  list<array<string, mixed>>  $personalidad
     * @param  list<array<string, mixed>>  $blandas
     * @return array<string, mixed>
     */
    private function evaluarCompletitud(
        Postulacion $postulacion,
        Collection $resultados,
        array $personalidad,
        array $blandas,
        ?string $instrumentoBlandas,
    ): array {
        $factoresConDato = collect($personalidad)->filter(fn ($fila) => $fila['esten_real'] !== null)->count();
        $intento16 = $this->ultimoIntento16Pf($postulacion);

        $personalidadCompleta = $intento16 !== null
            && $intento16->estado === EstadoIntento::Finalizado
            && $this->intentoRespondioBateria($intento16)
            && $factoresConDato >= 16;

        $codigosEsperados = $instrumentoBlandas !== null
            ? (self::DIMENSIONES_POR_INSTRUMENTO_BLANDA[$instrumentoBlandas] ?? [])
            : [];
        $blandasConDato = collect($blandas)
            ->filter(fn ($fila) => $fila['valor_real'] !== null
                && ($codigosEsperados === [] || in_array($fila['codigo'], $codigosEsperados, true)))
            ->count();
        $totalBlandasInstrumento = count($codigosEsperados);
        $habilidadesCompletas = $instrumentoBlandas !== null
            && $totalBlandasInstrumento > 0
            && $blandasConDato >= $totalBlandasInstrumento
            && $this->tieneHabilidadesBlandasCalificadas($resultados, $instrumentoBlandas);

        return [
            'personalidad_16pf_completa' => $personalidadCompleta,
            'factores_personalidad_con_dato' => $factoresConDato,
            'factores_personalidad_total' => 16,
            'preguntas_16pf_respondidas' => $intento16?->respuestas()->count(),
            'preguntas_16pf_esperadas' => $intento16?->procesoPrueba?->prueba?->limite_preguntas,
            'habilidades_blandas_completa' => $habilidadesCompletas,
            'instrumento_habilidades_blandas' => $instrumentoBlandas,
            'dimensiones_habilidades_esperadas' => $totalBlandasInstrumento,
            'dimensiones_habilidades_con_dato' => $blandasConDato,
            'inteligencia_completa' => $this->tieneResultadoCalificado($resultados, self::PRUEBAS_INTELIGENCIA),
            'especificas_completas' => collect($resultados)->contains(
                fn (ResultadoPrueba $r) => $this->instrumento($r)?->familia === FamiliaPrueba::HabilidadEspecifica
                    && $r->calificado_en !== null
                    && ($r->puntaje_normalizado !== null || $r->puntaje_bruto !== null)
            ),
            'entrevista_completa' => $this->tieneResultadoCalificado($resultados, ['ENTREVISTA']),
            'mensaje' => $personalidadCompleta
                ? null
                : 'El 16PF no está finalizado o faltan respuestas: no se calcula coincidencia de personalidad.',
            'mensaje_habilidades_blandas' => $instrumentoBlandas === null
                ? 'Aún no hay un test de habilidades blandas calificado para este postulante.'
                : ($habilidadesCompletas
                    ? null
                    : 'El test asignado de habilidades blandas no está completo o faltan dimensiones.'),
        ];
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     */
    private function instrumentoHabilidadesBlandas(Postulacion $postulacion, Collection $resultados): ?string
    {
        $alias = [
            'TEST_INTELIGENCIA_EMOCIONAL' => 'TMMS_24',
        ];

        foreach ($resultados->sortByDesc(fn (ResultadoPrueba $r) => $r->calificado_en ?? $r->id) as $resultado) {
            $codigo = $this->instrumento($resultado)?->codigo;
            if ($codigo === null) {
                continue;
            }
            $normalizado = $alias[$codigo] ?? $codigo;
            if (! isset(self::DIMENSIONES_POR_INSTRUMENTO_BLANDA[$normalizado])) {
                continue;
            }
            if ($resultado->calificado_en === null) {
                continue;
            }
            if ($resultado->puntaje_normalizado === null && $resultado->puntaje_bruto === null) {
                continue;
            }

            return $normalizado;
        }

        $asignacion = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', FamiliaPrueba::HabilidadBlanda))
            ->with('prueba')
            ->latest('id')
            ->first();

        $codigoAsignado = $asignacion?->prueba?->codigo;
        if ($codigoAsignado === null) {
            return null;
        }

        $normalizado = $alias[$codigoAsignado] ?? $codigoAsignado;

        return isset(self::DIMENSIONES_POR_INSTRUMENTO_BLANDA[$normalizado]) ? $normalizado : null;
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     */
    private function tieneHabilidadesBlandasCalificadas(Collection $resultados, string $instrumento): bool
    {
        $codigos = match ($instrumento) {
            'TMMS_24' => ['TMMS_24', 'TEST_INTELIGENCIA_EMOCIONAL'],
            'TEST_HABILIDADES_BLANDAS' => ['TEST_HABILIDADES_BLANDAS'],
            default => [],
        };

        return $codigos !== [] && $this->tieneResultadoCalificado($resultados, $codigos);
    }

    /**
     * @param  list<array<string, mixed>>  $blandas
     * @return list<array<string, mixed>>
     */
    private function filtrarPerfilBlandasPorInstrumento(array $blandas, ?string $instrumento): array
    {
        if ($instrumento === 'TEST_HABILIDADES_BLANDAS') {
            return $blandas;
        }

        if ($instrumento === null || ! isset(self::DIMENSIONES_POR_INSTRUMENTO_BLANDA[$instrumento])) {
            return $blandas;
        }

        $codigos = self::DIMENSIONES_POR_INSTRUMENTO_BLANDA[$instrumento];

        return array_values(array_filter(
            $blandas,
            fn (array $fila): bool => in_array($fila['codigo'], $codigos, true),
        ));
    }

    private function ultimoIntento16Pf(Postulacion $postulacion): ?IntentoPrueba
    {
        return IntentoPrueba::query()
            ->with(['procesoPrueba.prueba'])
            ->withCount('respuestas')
            ->where('postulacion_id', $postulacion->id)
            ->whereHas('procesoPrueba.prueba', fn ($consulta) => $consulta->whereIn('codigo', ['16PF_FORMA_A', '16PF_FORMA_B']))
            ->orderByDesc('id')
            ->first();
    }

    private function intentoRespondioBateria(IntentoPrueba $intento): bool
    {
        $esperadas = (int) ($intento->procesoPrueba?->prueba?->limite_preguntas ?? 187);
        if ($esperadas <= 0) {
            return $intento->estado === EstadoIntento::Finalizado;
        }

        return (int) $intento->respuestas_count >= $esperadas;
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @param  list<string>  $codigosPrueba
     */
    private function tieneResultadoCalificado(Collection $resultados, array $codigosPrueba): bool
    {
        return $resultados->contains(function (ResultadoPrueba $resultado) use ($codigosPrueba): bool {
            $codigo = $this->instrumento($resultado)?->codigo;
            if ($codigo === null || ! in_array($codigo, $codigosPrueba, true)) {
                return false;
            }

            return $resultado->calificado_en !== null
                && ($resultado->puntaje_normalizado !== null || $resultado->puntaje_bruto !== null);
        });
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
                    'porcentaje_aproximacion' => $estenReal !== null && $ideal !== null
                        ? $this->aproximacion($estenReal, $ideal)
                        : null,
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
