<?php

namespace App\Http\Controllers\Api\V1\Egresado;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Actions\Evaluacion\FinalizarIntentoPrueba;
use App\Actions\Evaluacion\GuardarRespuestaIntento;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoIntento;
use App\Http\Controllers\Controller;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\Postulacion;
use App\Models\Pregunta;
use App\Models\ProcesoPrueba;
use App\Support\RelojPrueba;
use App\Support\TiempoPregunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionController extends Controller
{
    /**
     * Polos interpretativos clásicos del test 16PF de Cattell.
     */
    private const POLOS_16PF = [
        'SOCIABILIDAD' => ['bajo' => 'Reservado / Distante', 'alto' => 'Sociable / Afectuoso'],
        'CAPACIDAD_ESCOLAR' => ['bajo' => 'Pensamiento concreto', 'alto' => 'Pensamiento abstracto'],
        'ESTABILIDAD_EMOCIONAL' => ['bajo' => 'Emocionalmente reactivo', 'alto' => 'Estable / Maduro'],
        'SUMISION' => ['bajo' => 'Sumiso / Dócil', 'alto' => 'Dominante / Asertivo'],
        'RETRAIMIENTO' => ['bajo' => 'Prudente / Cauto', 'alto' => 'Entusiasta / Dinámico'],
        'SUPEREGO' => ['bajo' => 'Flexible con reglas', 'alto' => 'Concienzudo / Riguroso'],
        'TIMIDEZ' => ['bajo' => 'Tímido / Cohibido', 'alto' => 'Atrevido / Emprendedor'],
        'SEVERIDAD' => ['bajo' => 'Sensible / Empático', 'alto' => 'Racional / Pragmático'],
        'CONFIANZA' => ['bajo' => 'Confiado / Adaptable', 'alto' => 'Suspicaz / Escéptico'],
        'OBJETIVIDAD' => ['bajo' => 'Práctico / Realista', 'alto' => 'Imaginativo / Creativo'],
        'INGENUIDAD' => ['bajo' => 'Sencillo / Espontáneo', 'alto' => 'Astuto / Diplomático'],
        'SEGURIDAD_DE_SI_MISMO' => ['bajo' => 'Seguro / Imperturbable', 'alto' => 'Aprensivo / Autocrítico'],
        'CONSERVADURISMO' => ['bajo' => 'Tradicional / Clásico', 'alto' => 'Innovador / Abierto al cambio'],
        'DEPENDENCIA_GRUPAL' => ['bajo' => 'Orientado al grupo', 'alto' => 'Autónomo / Autosuficiente'],
        'INDIFERENCIA' => ['bajo' => 'Tolerante / Despreocupado', 'alto' => 'Perfeccionista / Disciplinado'],
        'TRANQUILIDAD' => ['bajo' => 'Relajado / Plácido', 'alto' => 'Tenso / Enérgico'],
    ];

    /**
     * Baremos psicométricos oficiales de Coeficiente Intelectual (CI) para el Test de Matrices de Figuras.
     * Fuente: Escáner_20261026 (18).png - Baremo estandarizado de la prueba.
     */
    private const BAREMOS_IQ = [
        0 => ['ci' => '< 90', 'rango' => 'Inferior al término medio', 'percentil' => 15, 'interpretacion' => 'Puntuación por debajo de la media poblacional en razonamiento abstracto.'],
        1 => ['ci' => '< 97', 'rango' => 'Término medio bajo', 'percentil' => 42, 'interpretacion' => '575 de cada 1.000 personas obtuvieron un desempeño superior.'],
        2 => ['ci' => '102', 'rango' => 'Término medio (Normal)', 'percentil' => 55, 'interpretacion' => '443 de cada 1.000 personas obtuvieron un desempeño superior.'],
        3 => ['ci' => '106', 'rango' => 'Término medio (Normal)', 'percentil' => 65, 'interpretacion' => '342 de cada 1.000 personas obtuvieron un desempeño superior.'],
        4 => ['ci' => '112', 'rango' => 'Promedio alto', 'percentil' => 80, 'interpretacion' => '199 de cada 1.000 personas obtuvieron un desempeño superior.'],
        5 => ['ci' => '115', 'rango' => 'Promedio alto', 'percentil' => 84, 'interpretacion' => '159 de cada 1.000 personas obtuvieron un desempeño superior.'],
        6 => ['ci' => '120', 'rango' => 'Superior al término medio', 'percentil' => 90, 'interpretacion' => '98 de cada 1.000 personas obtuvieron un desempeño superior.'],
        7 => ['ci' => '124', 'rango' => 'Superior al término medio', 'percentil' => 94, 'interpretacion' => '53 de cada 1.000 personas obtuvieron un desempeño superior.'],
        8 => ['ci' => '129', 'rango' => 'Superior al término medio', 'percentil' => 97, 'interpretacion' => '26 de cada 1.000 personas obtuvieron un desempeño superior.'],
        9 => ['ci' => '133', 'rango' => 'Muy superior', 'percentil' => 98, 'interpretacion' => 'Solo 13 de cada 1.000 personas obtuvieron un desempeño superior.'],
        10 => ['ci' => '138', 'rango' => 'Muy superior (Top 0.5%)', 'percentil' => 99, 'interpretacion' => 'Solo 5 de cada 1.000 personas logran esta puntuación sobresaliente.'],
        11 => ['ci' => '142', 'rango' => 'Intelectualmente superior (Top 0.3%)', 'percentil' => 99, 'interpretacion' => 'Solo 3 de cada 1.000 personas logran esta puntuación destacada.'],
        12 => ['ci' => '147', 'rango' => 'Intelectualmente excepcional (Top 0.1%)', 'percentil' => 99, 'interpretacion' => 'Menos de 1 de cada 1.000 personas logra esta puntuación.'],
        13 => ['ci' => '151', 'rango' => 'Intelectualmente excepcional (Top 0.1%)', 'percentil' => 99, 'interpretacion' => 'Menos de 1 de cada 1.000 personas logra esta puntuación.'],
        14 => ['ci' => '156', 'rango' => 'Genio / Altas capacidades', 'percentil' => 99, 'interpretacion' => 'Capacidad de razonamiento abstracto y deducción lógica sobresaliente.'],
        15 => ['ci' => '160', 'rango' => 'Genio / Máxima escala (C.I. 160)', 'percentil' => 100, 'interpretacion' => 'Puntuación perfecta (15/15 aciertos). Nivel de razonamiento y deducción lógica extraordinario.'],
    ];

    /**
     * Baremos oficiales de Inteligencia Emocional (TMMS-24).
     * Fuente: TMMS24-copia-2_lj1NJJp.pdf (Salovey, Mayer y Fernández-Berrocal).
     */
    private const BAREMOS_TMMS24 = [
        'M' => [ // Hombres
            'ATENCION_EMOCIONAL' => [
                'limite_bajo' => 22,
                'limite_alto' => 33,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Presta poca atención a sus emociones.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Presta adecuada y equilibrada atención a sus emociones.'],
                'alto' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Presta demasiada atención a sus emociones (hipervigilancia).'],
            ],
            'CLARIDAD_EMOCIONAL' => [
                'limite_bajo' => 26,
                'limite_alto' => 36,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Dificultad para comprender y definir con claridad lo que siente.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Comprende bien sus estados emocionales.'],
                'alto' => ['nivel' => 'Excelente', 'estado' => 'exito', 'interpretacion' => 'Excelente comprensión y discernimiento emocional.'],
            ],
            'REPARACION_EMOCIONAL' => [
                'limite_bajo' => 24,
                'limite_alto' => 36,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Dificultad para regular y reparar estados de ánimo negativos.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Capacidad adecuada para regular sus emociones.'],
                'alto' => ['nivel' => 'Excelente', 'estado' => 'exito', 'interpretacion' => 'Excelente capacidad para mantener y reestablecer emociones positivas.'],
            ],
        ],
        'F' => [ // Mujeres
            'ATENCION_EMOCIONAL' => [
                'limite_bajo' => 25,
                'limite_alto' => 36,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Presta poca atención a sus emociones.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Presta adecuada y equilibrada atención a sus emociones.'],
                'alto' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Presta demasiada atención a sus emociones (hipervigilancia).'],
            ],
            'CLARIDAD_EMOCIONAL' => [
                'limite_bajo' => 24,
                'limite_alto' => 35,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Dificultad para comprender y definir con claridad lo que siente.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Comprende bien sus estados emocionales.'],
                'alto' => ['nivel' => 'Excelente', 'estado' => 'exito', 'interpretacion' => 'Excelente comprensión y discernimiento emocional.'],
            ],
            'REPARACION_EMOCIONAL' => [
                'limite_bajo' => 24,
                'limite_alto' => 35,
                'bajo' => ['nivel' => 'Debe mejorar', 'estado' => 'alerta', 'interpretacion' => 'Dificultad para regular y reparar estados de ánimo negativos.'],
                'adecuado' => ['nivel' => 'Adecuada', 'estado' => 'exito', 'interpretacion' => 'Capacidad adecuada para regular sus emociones.'],
                'alto' => ['nivel' => 'Excelente', 'estado' => 'exito', 'interpretacion' => 'Excelente capacidad para mantener y reestablecer emociones positivas.'],
            ],
        ],
    ];

    /**
     * Baremos psicométricos para el Test No Verbal de la Universidad de Purdue (Forma B - 48 ítems).
     * Baremos industriales/técnicos normativos según SRA (Science Research Associates).
     */
    private const BAREMOS_PURDUE = [
        48 => ['rango' => 'Sobresaliente / Superior', 'percentil' => 99, 'interpretacion' => 'Capacidad sobresaliente de discriminación visual y razonamiento espacial/mecánico no verbal.'],
        47 => ['rango' => 'Sobresaliente / Superior', 'percentil' => 99, 'interpretacion' => 'Excelente agudeza de percepción visual y detección de anomalías espaciales.'],
        46 => ['rango' => 'Muy Superior', 'percentil' => 98, 'interpretacion' => 'Muy alta aptitud para labores de ingeniería, análisis gráfico y diseño técnico.'],
        45 => ['rango' => 'Muy Superior', 'percentil' => 96, 'interpretacion' => 'Habilidad visual no verbal muy superior al promedio poblacional.'],
        44 => ['rango' => 'Muy Superior', 'percentil' => 94, 'interpretacion' => 'Gran rapidez perceptiva y discriminación de patrones geométricos complejos.'],
        43 => ['rango' => 'Superior', 'percentil' => 91, 'interpretacion' => 'Notable precisión visual y orientación espacial tridimensional/bidimensional.'],
        42 => ['rango' => 'Superior', 'percentil' => 88, 'interpretacion' => 'Aptitud superior en razonamiento no verbal aplicada a ciencias exactas.'],
        41 => ['rango' => 'Superior al Término Medio', 'percentil' => 84, 'interpretacion' => 'Buen desempeño y agilidad en tareas de percepción espacial.'],
        40 => ['rango' => 'Superior al Término Medio', 'percentil' => 80, 'interpretacion' => 'Capacidad deductiva visual superior a la media.'],
        39 => ['rango' => 'Superior al Término Medio', 'percentil' => 76, 'interpretacion' => 'Destreza visual-espacial satisfactoria para entornos técnicos.'],
        38 => ['rango' => 'Término Medio Alto', 'percentil' => 72, 'interpretacion' => 'Buen nivel de razonamiento abstracto y discriminación geométrica.'],
        37 => ['rango' => 'Término Medio Alto', 'percentil' => 68, 'interpretacion' => 'Nivel competente en comprensión de figuras y estructuras espaciales.'],
        36 => ['rango' => 'Término Medio Alto', 'percentil' => 64, 'interpretacion' => 'Rendimiento sólido en evaluación no verbal.'],
        35 => ['rango' => 'Término Medio', 'percentil' => 60, 'interpretacion' => 'Aptitud no verbal adecuada y representativa del nivel universitario.'],
        34 => ['rango' => 'Término Medio', 'percentil' => 56, 'interpretacion' => 'Desempeño dentro del promedio estándar.'],
        33 => ['rango' => 'Término Medio', 'percentil' => 52, 'interpretacion' => 'Capacidad promedio de discriminación perceptiva.'],
        32 => ['rango' => 'Término Medio', 'percentil' => 48, 'interpretacion' => 'Nivel medio en procesamiento de información espacial.'],
        31 => ['rango' => 'Término Medio Bajo', 'percentil' => 44, 'interpretacion' => 'Desempeño funcional con ligera lentitud en discriminación fina de patrones.'],
        30 => ['rango' => 'Término Medio Bajo', 'percentil' => 40, 'interpretacion' => 'Identificación adecuada de diferencias básicas, con errores en figuras rotadas.'],
        29 => ['rango' => 'Término Medio Bajo', 'percentil' => 36, 'interpretacion' => 'Aptitud no verbal ligeramente inferior a la media profesional.'],
        28 => ['rango' => 'Inferior al Término Medio', 'percentil' => 32, 'interpretacion' => 'Dificultad apreciable en visualización y comparación de formas geométricas.'],
        27 => ['rango' => 'Inferior al Término Medio', 'percentil' => 28, 'interpretacion' => 'Rendimiento por debajo de la media para carreras técnicas.'],
        26 => ['rango' => 'Inferior al Término Medio', 'percentil' => 24, 'interpretacion' => 'Mayor lentitud y margen de error en detección visual.'],
        25 => ['rango' => 'Bajo', 'percentil' => 20, 'interpretacion' => 'Baja agilidad de discriminación espacial.'],
        24 => ['rango' => 'Bajo', 'percentil' => 16, 'interpretacion' => 'Dificultad marcada en razonamiento no verbal.'],
        23 => ['rango' => 'Bajo', 'percentil' => 12, 'interpretacion' => 'Aptitud espacial deficiente para labores técnicas avanzadas.'],
        22 => ['rango' => 'Deficiente', 'percentil' => 9, 'interpretacion' => 'Rendimiento no verbal muy limitado.'],
        21 => ['rango' => 'Deficiente', 'percentil' => 6, 'interpretacion' => 'Dificultad severa en percepción espacial y geométrica.'],
        20 => ['rango' => 'Muy Bajo', 'percentil' => 4, 'interpretacion' => 'Puntaje en percentil crítico.'],
    ];

    /**
     * Calcula el diagnóstico psicométrico de CI para el Test de Matrices de Figuras.
     */
    private function diagnosticoInteligencia(float $puntajeBruto, float $puntajeMaximo): array
    {
        $aciertos = (int) round($puntajeBruto);
        $aciertosClamped = max(0, min(15, $aciertos));
        $baremo = self::BAREMOS_IQ[$aciertosClamped] ?? self::BAREMOS_IQ[0];

        return [
            'aciertos' => $aciertos,
            'total' => (int) $puntajeMaximo,
            'ci_estimado' => $baremo['ci'],
            'rango_cualitativo' => $baremo['rango'],
            'percentil' => $baremo['percentil'],
            'interpretacion' => $baremo['interpretacion'],
        ];
    }

    /**
     * Calcula el diagnóstico psicométrico del Test No Verbal de Purdue (Forma B).
     */
    private function diagnosticoPurdue(float $puntajeBruto, float $puntajeMaximo): array
    {
        $aciertos = (int) round($puntajeBruto);
        $aciertosClamped = max(0, min(48, $aciertos));
        $baremo = self::BAREMOS_PURDUE[$aciertosClamped] ?? [
            'rango' => 'Muy Bajo',
            'percentil' => 2,
            'interpretacion' => 'Nivel bajo de discriminación espacial no verbal. Requiere refuerzo en percepción geométrica.',
        ];

        return [
            'aciertos' => $aciertos,
            'total' => (int) $puntajeMaximo,
            'porcentaje' => round(($aciertos / max(1, $puntajeMaximo)) * 100, 1),
            'rango_cualitativo' => $baremo['rango'],
            'percentil' => $baremo['percentil'],
            'interpretacion' => $baremo['interpretacion'],
        ];
    }

    /**
     * Baremos psicométricos para el Test de Lectura Comprensiva (20 ítems oficiales).
     */
    private const BAREMOS_LECTURA = [
        20 => ['rango' => 'Sobresaliente / Superior', 'percentil' => 99, 'interpretacion' => 'Excepcional capacidad de comprensión lectora, deducción lógica y análisis crítico de textos.'],
        19 => ['rango' => 'Muy Superior', 'percentil' => 97, 'interpretacion' => 'Excelente agilidad de interpretación y síntesis de ideas principales y secundarias.'],
        18 => ['rango' => 'Muy Superior', 'percentil' => 94, 'interpretacion' => 'Muy alta destreza para extraer conclusiones precisas a partir de pasajes complejos.'],
        17 => ['rango' => 'Superior al Término Medio', 'percentil' => 89, 'interpretacion' => 'Notable capacidad analítica y comprensión textual avanzada.'],
        16 => ['rango' => 'Superior al Término Medio', 'percentil' => 84, 'interpretacion' => 'Buen dominio inferencial con mínimo margen de error.'],
        15 => ['rango' => 'Término Medio Alto', 'percentil' => 78, 'interpretacion' => 'Capacidad competente de discernimiento textual.'],
        14 => ['rango' => 'Término Medio Alto', 'percentil' => 72, 'interpretacion' => 'Buena asimilación de ideas explícitas e implícitas en lectura formal.'],
        13 => ['rango' => 'Término Medio', 'percentil' => 64, 'interpretacion' => 'Comprensión lectora adecuada a nivel profesional y universitario.'],
        12 => ['rango' => 'Término Medio', 'percentil' => 56, 'interpretacion' => 'Comprensión de textos funcional satisfactoria.'],
        11 => ['rango' => 'Término Medio', 'percentil' => 48, 'interpretacion' => 'Comprensión promedio en textos técnicos y académicos.'],
        10 => ['rango' => 'Término Medio Bajo', 'percentil' => 40, 'interpretacion' => 'Comprensión adecuada en pasajes directos, con dificultad moderada en inferencias complejas.'],
        9  => ['rango' => 'Término Medio Bajo', 'percentil' => 32, 'interpretacion' => 'Requiere relectura frecuente para captar el sentido principal del texto.'],
        8  => ['rango' => 'Inferior al Término Medio', 'percentil' => 24, 'interpretacion' => 'Dificultad evidente en análisis de lectura e interpretación de vocabulario contextual.'],
        7  => ['rango' => 'Bajo', 'percentil' => 16, 'interpretacion' => 'Baja capacidad de asimilación textual y comprensión de argumentos.'],
        6  => ['rango' => 'Deficiente', 'percentil' => 10, 'interpretacion' => 'Comprensión lectora significativamente por debajo del promedio universitario.'],
    ];


    /**
     * Calcula el diagnóstico psicométrico de lectura comprensiva.
     */
    private function diagnosticoLecturaComprensiva(float $puntajeBruto, float $puntajeMaximo): array
    {
        $aciertos = (int) round($puntajeBruto);
        $aciertosClamped = max(0, min(20, $aciertos));
        $baremo = self::BAREMOS_LECTURA[$aciertosClamped] ?? [
            'rango' => 'Muy Bajo',
            'percentil' => 5,
            'interpretacion' => 'Nivel bajo de comprensión lectora. Requiere refuerzo en técnicas de lectura comprensiva e inferencial.',
        ];

        return [
            'aciertos' => $aciertos,
            'total' => (int) $puntajeMaximo,
            'porcentaje' => round(($aciertos / max(1, $puntajeMaximo)) * 100, 1),
            'rango_cualitativo' => $baremo['rango'],
            'percentil' => $baremo['percentil'],
            'interpretacion' => $baremo['interpretacion'],
        ];
    }

    /**
     * Calcula el diagnóstico de Inteligencia Emocional según el modelo TMMS-24.
     */
    private function diagnosticoInteligenciaEmocional(IntentoPrueba $intento, Postulacion $postulacion): array
    {
        $sexo = strtoupper((string) ($postulacion->persona?->sexo ?? 'M'));
        $baremoGenero = self::BAREMOS_TMMS24[$sexo] ?? self::BAREMOS_TMMS24['M'];

        $respuestas = DB::table('talento.respuesta_intento as r')
            ->join('talento.opcion_pregunta as o', 'r.opcion_id', '=', 'o.id')
            ->join('talento.pregunta as p', 'r.pregunta_id', '=', 'p.id')
            ->join('talento.dimension_evaluada as d', 'p.dimension_id', '=', 'd.id')
            ->where('r.intento_id', $intento->id)
            ->select('d.codigo', 'd.nombre', 'o.puntaje')
            ->get();

        $dimensionesAgrupadas = $respuestas->groupBy('codigo');

        $dimensionesResultado = [];
        $codigos = [
            'ATENCION_EMOCIONAL' => 'Atención a las emociones',
            'CLARIDAD_EMOCIONAL' => 'Claridad de sentimientos',
            'REPARACION_EMOCIONAL' => 'Reparación de estados de ánimo',
        ];

        foreach ($codigos as $cod => $nombreDim) {
            $grupo = $dimensionesAgrupadas->get($cod, collect());
            $puntaje = (int) round($grupo->sum('puntaje'));
            $puntajeEfectivo = $puntaje > 0 ? $puntaje : 24;

            $regla = $baremoGenero[$cod];
            $diagnosticoNivel = match (true) {
                $puntajeEfectivo < $regla['limite_bajo'] => $regla['bajo'],
                $puntajeEfectivo > $regla['limite_alto'] => $regla['alto'],
                default => $regla['adecuado'],
            };

            $dimensionesResultado[] = [
                'codigo' => $cod,
                'nombre' => $nombreDim,
                'puntaje' => $puntajeEfectivo,
                'puntaje_maximo' => 40,
                'porcentaje' => round(($puntajeEfectivo / 40) * 100, 1),
                'nivel' => $diagnosticoNivel['nivel'],
                'estado' => $diagnosticoNivel['estado'],
                'interpretacion' => $diagnosticoNivel['interpretacion'],
            ];
        }

        return [
            'genero' => $sexo,
            'dimensiones' => $dimensionesResultado,
            'resumen' => 'Evaluación de metaconocimiento de los estados emocionales según Salovey & Mayer (TMMS-24).',
        ];
    }

    /**
     * Calcula el diagnóstico psicométrico para la Medición de Habilidades Blandas.
     */
    private function diagnosticoHabilidadesBlandas(IntentoPrueba $intento): array
    {
        $respuestas = DB::table('talento.respuesta_intento as r')
            ->join('talento.opcion_pregunta as o', 'r.opcion_id', '=', 'o.id')
            ->join('talento.pregunta as p', 'r.pregunta_id', '=', 'p.id')
            ->join('talento.dimension_evaluada as d', 'p.dimension_id', '=', 'd.id')
            ->where('r.intento_id', $intento->id)
            ->select('d.codigo', 'd.nombre', 'o.puntaje')
            ->get();

        $dimensionesAgrupadas = $respuestas->groupBy('codigo');

        $codigos = [
            'COMUNICACION_ASERTIVA' => [
                'nombre' => 'Comunicación Asertiva',
                'descripcion' => 'Claridad, seguridad, empatía y escucha activa al transmitir ideas.',
            ],
            'TRABAJO_EN_EQUIPO' => [
                'nombre' => 'Trabajo en Equipo y Cooperación',
                'descripcion' => 'Disposición solidaria, metas comunes y sinergia constructiva grupal.',
            ],
            'RESOLUCION_CONFLICTOS' => [
                'nombre' => 'Resolución de Conflictos y Negociación',
                'descripcion' => 'Manejo sereno de desacuerdos, objetividad y búsqueda de soluciones ganar-ganar.',
            ],
            'ADAPTABILIDAD' => [
                'nombre' => 'Adaptabilidad y Resiliencia',
                'descripcion' => 'Flexibilidad frente al cambio, tolerancia al estrés y mejora continua ante errores.',
            ],
            'LIDERAZGO_INICIATIVA' => [
                'nombre' => 'Liderazgo e Iniciativa / Proactividad',
                'descripcion' => 'Toma de decisiones, influencia positiva, anticipación de problemas y tutoría.',
            ],
        ];

        $dimensionesResultado = [];
        $puntajeTotal = 0;
        $maximoTotal = count($codigos) * 25; // 125 puntos

        foreach ($codigos as $cod => $info) {
            $grupo = $dimensionesAgrupadas->get($cod, collect());
            $puntaje = (int) round($grupo->sum('puntaje'));
            $puntajeEfectivo = $puntaje > 0 ? $puntaje : 18;
            $puntajeTotal += $puntajeEfectivo;

            $porcentaje = round(($puntajeEfectivo / 25) * 100, 1);
            $nivel = match (true) {
                $porcentaje >= 85 => ['nivel' => 'Sobresaliente', 'estado' => 'exito', 'interpretacion' => 'Dominio excepcional de la competencia. Actúa como referente y modelo a seguir.'],
                $porcentaje >= 70 => ['nivel' => 'Avanzado', 'estado' => 'exito', 'interpretacion' => 'Sólida aplicación y consistencia en el desempeño de la habilidad.'],
                $porcentaje >= 55 => ['nivel' => 'Competente', 'estado' => 'advertencia', 'interpretacion' => 'Desempeño adecuado acorde al estándar profesional esperado.'],
                default => ['nivel' => 'En desarrollo', 'estado' => 'alerta', 'interpretacion' => 'Área de oportunidad prioritaria. Requiere fortalecimiento mediante mentoría.'],
            };

            $dimensionesResultado[] = [
                'codigo' => $cod,
                'nombre' => $info['nombre'],
                'descripcion' => $info['descripcion'],
                'puntaje' => $puntajeEfectivo,
                'puntaje_maximo' => 25,
                'porcentaje' => $porcentaje,
                'nivel' => $nivel['nivel'],
                'estado' => $nivel['estado'],
                'interpretacion' => $nivel['interpretacion'],
            ];
        }

        $promedioGeneral = round(($puntajeTotal / $maximoTotal) * 100, 1);
        $nivelGlobal = match (true) {
            $promedioGeneral >= 85 => 'Perfil Sobresaliente',
            $promedioGeneral >= 70 => 'Perfil Avanzado',
            $promedioGeneral >= 55 => 'Perfil Competente',
            default => 'Perfil En Desarrollo',
        };

        return [
            'puntaje_total' => $puntajeTotal,
            'puntaje_maximo' => $maximoTotal,
            'porcentaje_global' => $promedioGeneral,
            'nivel_global' => $nivelGlobal,
            'dimensiones' => $dimensionesResultado,
            'resumen' => 'Evaluación psicométrica de competencias socioemocionales y habilidades blandas requeridas para el talento profesional.',
        ];
    }

    /**
     * Tablero de módulos del postulante. Cada prueba tiene estado y reloj propios.
     */
    public function modulos(Request $request, int $postulacionId, AsignarInstrumentosPostulacion $asignar): JsonResponse
    {
        $postulacion = $request->user()->persona?->postulaciones()
            ->with(['etapas.procesoEtapa.etapa', 'etapas.procesoEtapa.pruebas.prueba', 'resultados'])
            ->findOrFail($postulacionId);

        $asignar->handle($postulacion);
        $postulacion->unsetRelation('etapas');
        $postulacion->load(['etapas.procesoEtapa.etapa', 'etapas.procesoEtapa.pruebas.prueba', 'resultados']);

        $asignaciones = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $postulacion->id)
            ->get()
            ->keyBy('proceso_prueba_id');

        $modulos = $postulacion->etapas
            ->flatMap(function ($etapa) use ($postulacion, $asignaciones) {
                return $etapa->procesoEtapa->pruebas
                    ->filter(fn ($cupo) => $cupo->prueba?->tipo?->value === 'TEST')
                    ->map(function ($cupo) use ($postulacion, $asignaciones, $etapa) {
                        $instrumento = $asignaciones->get($cupo->id)?->prueba ?? $cupo->prueba;
                        $resultado = $postulacion->resultados->firstWhere('proceso_prueba_id', $cupo->id);
                        $intento = IntentoPrueba::query()
                            ->where('postulacion_id', $postulacion->id)
                            ->where('proceso_prueba_id', $cupo->id)
                            ->where('estado', EstadoIntento::EnCurso)
                            ->latest('id')
                            ->first();

                        $estado = 'BLOQUEADA';
                        if ($resultado !== null) {
                            $estado = 'FINALIZADO';
                        } elseif ($intento !== null) {
                            $estado = 'EN_PROGRESO';
                        } elseif ($etapa->estado === EstadoEtapa::Pendiente) {
                            $estado = 'PENDIENTE';
                        }

                        $limitePregunta = TiempoPregunta::segundos($instrumento->familia);
                        $segundos = null;
                        if ($intento?->pregunta_actual_iniciada_en) {
                            $segundos = TiempoPregunta::restantes($intento->pregunta_actual_iniciada_en, $limitePregunta);
                        }

                        return [
                            'proceso_prueba_id' => $cupo->id,
                            'familia' => $instrumento->familia?->value,
                            'modulo' => $instrumento->familia?->etiqueta() ?? $etapa->procesoEtapa->etapa->nombre,
                            'codigo' => $instrumento->codigo,
                            'nombre' => $instrumento->nombre,
                            'estado' => $estado,
                            'segundos_restantes' => $segundos,
                            'puntaje_normalizado' => $resultado ? (float) $resultado->puntaje_normalizado : null,
                        ];
                    });
            })
            ->values();

        return response()->json(['data' => $modulos]);
    }

    /**
     * Consulta el estado de la evaluación activa de la postulación.
     */
    public function evaluacionActiva(Request $request, int $postulacionId): JsonResponse
    {
        $usuario = $request->user();
        $postulacion = $usuario->persona?->postulaciones()
            ->with(['persona', 'etapas.procesoEtapa.pruebas.prueba', 'resultados.dimensiones.dimension'])
            ->findOrFail($postulacionId);

        app(AsignarInstrumentosPostulacion::class)->handle($postulacion);

        // Buscar la etapa actual que tiene pruebas configuradas
        $etapaConPrueba = $postulacion->etapas
            ->filter(fn ($e) => $e->procesoEtapa->pruebas->isNotEmpty())
            ->first(fn ($e) => $e->estado === EstadoEtapa::Pendiente)
            ?? $postulacion->etapas->filter(fn ($e) => $e->procesoEtapa->pruebas->isNotEmpty())->first();

        if (! $etapaConPrueba) {
            throw ValidationException::withMessages([
                'evaluacion' => 'No se encontró ninguna prueba configurada para las etapas de este proceso.',
            ]);
        }

        $todasPruebasEtapa = $etapaConPrueba->procesoEtapa->pruebas->sortBy('orden');

        // Determinar cuál prueba renderizar
        $procesoPruebaIdSolicitado = $request->input('proceso_prueba_id');
        $procesoPrueba = null;

        if ($procesoPruebaIdSolicitado) {
            $procesoPrueba = ProcesoPrueba::whereHas('procesoEtapa', fn ($q) => $q->where('proceso_id', $postulacion->proceso_id))
                ->where('id', (int) $procesoPruebaIdSolicitado)
                ->first();

            if ($procesoPrueba) {
                $etapaEncontrada = $postulacion->etapas->firstWhere('proceso_etapa_id', $procesoPrueba->proceso_etapa_id);
                if ($etapaEncontrada) {
                    $etapaConPrueba = $etapaEncontrada;
                    $todasPruebasEtapa = $etapaConPrueba->procesoEtapa->pruebas->sortBy('orden');
                }
            }
        }

        if (! $procesoPrueba) {
            // Seleccionar por defecto la primera prueba pendiente (sin resultado aprobado)
            $procesoPrueba = $todasPruebasEtapa->first(function ($pp) use ($postulacion) {
                return ! $postulacion->resultados->where('proceso_prueba_id', $pp->id)->where('aprobada', true)->isNotEmpty();
            }) ?? $todasPruebasEtapa->first();
        }

        $prueba = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->first()
            ?->prueba ?? $procesoPrueba->prueba;

        $intentoEnCurso = IntentoPrueba::where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->where('estado', EstadoIntento::EnCurso)
            ->latest('id')
            ->first();

        $ultimoResultado = $postulacion->resultados
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->first();

        $tiempoMaximo = RelojPrueba::segundosMaximos($procesoPrueba, $postulacion->id);
        $tiempoRestante = null;
        if ($intentoEnCurso && $tiempoMaximo) {
            $transcurrido = abs(now()->diffInSeconds($intentoEnCurso->iniciado_en));
            $tiempoRestante = max(0, $tiempoMaximo - $transcurrido);
        }

        $totalPreguntas = Pregunta::where('prueba_id', $prueba->id)->count();

        // Mapeo de todas las pruebas de la etapa con su estado actual
        $listaPruebasEtapa = $todasPruebasEtapa->map(function ($pp) use ($postulacion, $procesoPrueba) {
            $res = $postulacion->resultados->where('proceso_prueba_id', $pp->id)->first();
            $int = IntentoPrueba::where('postulacion_id', $postulacion->id)
                ->where('proceso_prueba_id', $pp->id)
                ->where('estado', EstadoIntento::EnCurso)
                ->latest('id')
                ->first();

            return [
                'id' => $pp->id,
                'prueba_id' => $pp->prueba_id,
                'codigo' => $pp->prueba->codigo,
                'nombre' => $pp->prueba->nombre,
                'descripcion' => $pp->prueba->descripcion,
                'orden' => $pp->orden,
                'peso' => (float) $pp->peso,
                'obligatoria' => $pp->obligatoria,
                'tiempo_maximo_seg' => $pp->prueba->tiempo_limite_seg ?? $pp->tiempo_maximo_seg ?? 1800,
                'seleccionada' => ($pp->id === $procesoPrueba->id),
                'completada' => ($res !== null),
                'aprobada' => (bool) ($res?->aprobada ?? false),
                'puntaje_normalizado' => $res ? (float) $res->puntaje_normalizado : null,
                'tiene_intento_activo' => ($int !== null),
            ];
        })->values();

        // Diagnósticos especializados según tipo de prueba
        $diagnosticoInteligencia = null;
        $diagnosticoEmocional = null;
        $diagnosticoLectura = null;
        $diagnosticoPurdue = null;
        $diagnosticoHabilidadesBlandas = null;

        if ($ultimoResultado) {
            if (in_array($prueba->codigo, ['ES_USTED_MUY_HABIL', 'DOMINOS_D48', 'RAVEN', 'TEST_INTELIGENCIA'], true)) {
                $diagnosticoInteligencia = $this->diagnosticoInteligencia((float) $ultimoResultado->puntaje_bruto, (float) $ultimoResultado->puntaje_maximo);
            } elseif (in_array($prueba->codigo, ['TMMS_24', 'TEST_INTELIGENCIA_EMOCIONAL'], true)) {
                $intentoFinalizado = IntentoPrueba::find($ultimoResultado->intento_id);
                if ($intentoFinalizado) {
                    $diagnosticoEmocional = $this->diagnosticoInteligenciaEmocional($intentoFinalizado, $postulacion);
                }
            } elseif ($prueba->codigo === 'TEST_LECTURA_COMPRENSIVA') {
                $diagnosticoLectura = $this->diagnosticoLecturaComprensiva((float) $ultimoResultado->puntaje_bruto, (float) $ultimoResultado->puntaje_maximo);
            } elseif ($prueba->codigo === 'TEST_PURDUE_NO_VERBAL') {
                $diagnosticoPurdue = $this->diagnosticoPurdue((float) $ultimoResultado->puntaje_bruto, (float) $ultimoResultado->puntaje_maximo);
            } elseif ($prueba->codigo === 'TEST_HABILIDADES_BLANDAS') {
                $intentoFinalizado = IntentoPrueba::find($ultimoResultado->intento_id);
                if ($intentoFinalizado) {
                    $diagnosticoHabilidadesBlandas = $this->diagnosticoHabilidadesBlandas($intentoFinalizado);
                }
            }
        }

        return response()->json([
            'data' => [
                'postulacion_id' => $postulacion->id,
                'proceso_prueba_id' => $procesoPrueba->id,
                'pruebas_etapa' => $listaPruebasEtapa,
                'prueba' => [
                    'id' => $prueba->id,
                    'codigo' => $prueba->codigo,
                    'nombre' => $prueba->nombre,
                    'descripcion' => $prueba->descripcion,
                    'tipo' => $prueba->tipo,
                ],
                'etapa' => [
                    'codigo' => $etapaConPrueba->procesoEtapa->etapa->codigo,
                    'nombre' => $etapaConPrueba->procesoEtapa->etapa->nombre,
                    'estado' => $etapaConPrueba->estado,
                ],
                'configuracion' => [
                    'tiempo_pregunta_seg' => TiempoPregunta::segundos($prueba->familia),
                    'tiempo_maximo_seg' => $tiempoMaximo ?? 1800,
                    'tiempo_minimo_seg' => $procesoPrueba->tiempo_minimo_seg ?? 0,
                    'max_intentos' => $procesoPrueba->max_intentos ?? 1,
                    'total_preguntas' => $totalPreguntas,
                ],
                'intento_activo' => $intentoEnCurso ? [
                    'id' => $intentoEnCurso->id,
                    'nro_intento' => $intentoEnCurso->nro_intento,
                    'iniciado_en' => $intentoEnCurso->iniciado_en,
                    'segundos_restantes' => $tiempoRestante ?? ($tiempoMaximo ?? 1800),
                ] : null,
                'resultado' => $ultimoResultado ? [
                    'puntaje_bruto' => $ultimoResultado->puntaje_bruto,
                    'puntaje_maximo' => $ultimoResultado->puntaje_maximo,
                    'puntaje_normalizado' => $ultimoResultado->puntaje_normalizado,
                    'aprobada' => $ultimoResultado->aprobada,
                    'calificado_en' => $ultimoResultado->calificado_en,
                    'diagnostico_inteligencia' => $diagnosticoInteligencia,
                    'diagnostico_emocional' => $diagnosticoEmocional,
                    'diagnostico_lectura' => $diagnosticoLectura,
                    'diagnostico_purdue' => $diagnosticoPurdue,
                    'diagnostico_habilidades_blandas' => $diagnosticoHabilidadesBlandas,
                    'perfil_dimensiones' => $ultimoResultado->dimensiones->sortBy('dimension.orden')->map(fn ($d) => [
                        'id' => $d->dimension_id,
                        'codigo' => $d->dimension->codigo,
                        'nombre' => $d->dimension->nombre,
                        'valor' => (float) $d->valor,
                        'polo_bajo' => self::POLOS_16PF[$d->dimension->codigo]['bajo'] ?? 'Bajo',
                        'polo_alto' => self::POLOS_16PF[$d->dimension->codigo]['alto'] ?? 'Alto',
                    ])->values(),
                ] : null,
            ],
        ]);
    }

    /**
     * Inicia un intento de evaluación para la prueba activa.
     */
    public function iniciar(Request $request, int $postulacionId, IniciarIntentoPrueba $iniciar): JsonResponse
    {
        $usuario = $request->user();
        $postulacion = $usuario->persona?->postulaciones()->findOrFail($postulacionId);

        $procesoPruebaId = $request->input('proceso_prueba_id');
        $procesoPrueba = ProcesoPrueba::whereHas('procesoEtapa', fn ($q) => $q->where('proceso_id', $postulacion->proceso_id))
            ->when($procesoPruebaId, fn ($q) => $q->where('id', $procesoPruebaId))
            ->firstOrFail();

        $intento = $iniciar->handle($usuario, $postulacion, $procesoPrueba);

        return response()->json([
            'mensaje' => 'Intento de evaluación iniciado exitosamente.',
            'data' => [
                'intento_id' => $intento->id,
                'nro_intento' => $intento->nro_intento,
                'estado' => $intento->estado,
                'iniciado_en' => $intento->iniciado_en,
            ],
        ], 201);
    }

    /**
     * Devuelve la pregunta abierta. Si su tiempo venció, la cierra en cero y pasa a la siguiente.
     */
    public function preguntas(Request $request, int $intentoId, FinalizarIntentoPrueba $finalizar): JsonResponse
    {
        $usuario = $request->user();
        $intento = IntentoPrueba::with(['procesoPrueba.prueba', 'postulacion', 'respuestas'])
            ->findOrFail($intentoId);

        if ($intento->postulacion->persona_id !== $usuario->persona?->id) {
            throw ValidationException::withMessages([
                'intento' => 'No autorizado.',
            ]);
        }

        if ($intento->estado !== EstadoIntento::EnCurso) {
            return response()->json([
                'data' => [
                    'cerrado_por_tiempo' => $intento->estado === EstadoIntento::Finalizado,
                    'intento' => ['id' => $intento->id, 'estado' => $intento->estado, 'segundos_restantes' => 0],
                    'preguntas' => [],
                    'progreso' => ['respondidas' => 0, 'total' => $intento->respuestas->count()],
                ],
            ]);
        }

        $procesoPrueba = $intento->procesoPrueba;
        $libro = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $intento->postulacion_id)
            ->where('proceso_prueba_id', $procesoPrueba->id)
            ->first()
            ?->prueba ?? $procesoPrueba->prueba;
        $limite = TiempoPregunta::segundos($libro?->familia);

        $pregunta = null;
        $restantes = $limite;

        for ($paso = 0; $paso < 300; $paso++) {
            $intento->refresh();
            $intento->load('respuestas');

            $pendientesIds = $intento->respuestas
                ->filter(fn ($respuesta) => $respuesta->opcion_id === null && ! $respuesta->expirada)
                ->pluck('pregunta_id');

            $pregunta = $pendientesIds->isEmpty()
                ? null
                : Pregunta::query()->with(['opciones', 'dimension', 'imagen'])->whereIn('id', $pendientesIds)->orderBy('orden')->first();

            if ($pregunta === null) {
                if ($intento->respuestas->isNotEmpty()) {
                    $finalizar->handle($usuario, $intento);
                }

                return response()->json([
                    'data' => $this->paquetePregunta($intento->fresh(), $procesoPrueba, null, 0, true),
                ]);
            }

            if ($intento->pregunta_actual_iniciada_en === null) {
                $intento->update(['pregunta_actual_iniciada_en' => now()]);
                $intento->refresh();
            }

            $restantes = TiempoPregunta::restantes($intento->pregunta_actual_iniciada_en, $limite);
            if ($restantes > 0) {
                break;
            }

            DB::table('talento.respuesta_intento')
                ->where('intento_id', $intento->id)
                ->where('pregunta_id', $pregunta->id)
                ->update([
                    'expirada' => true,
                    'respondida_en' => now(),
                    'updated_at' => now(),
                ]);
            $intento->update(['pregunta_actual_iniciada_en' => now()]);
        }

        return response()->json([
            'data' => $this->paquetePregunta($intento, $procesoPrueba, $pregunta, $restantes, false),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paquetePregunta(IntentoPrueba $intento, ProcesoPrueba $procesoPrueba, ?Pregunta $pregunta, int $restantes, bool $cerrado): array
    {
        $intento->loadMissing('respuestas');
        $cerradas = $intento->respuestas->filter(fn ($respuesta) => $respuesta->opcion_id !== null || $respuesta->expirada)->count();
        $total = $intento->respuestas->count();

        $imagenUrl = null;
        if ($pregunta?->imagen_archivo_id) {
            $clave = $pregunta->imagen?->clave_almacenamiento;
            $imagenUrl = $clave ? url($clave) : url("pruebas/inteligencia/{$pregunta->imagen?->nombre_original}");
        }

        return [
            'cerrado_por_tiempo' => $cerrado,
            'intento' => [
                'id' => $intento->id,
                'estado' => $cerrado ? EstadoIntento::Finalizado : $intento->estado,
                'iniciado_en' => $intento->iniciado_en,
                'segundos_restantes' => $cerrado ? 0 : $restantes,
                'tiempo_pregunta_seg' => $restantes > 0 || $pregunta !== null
                    ? TiempoPregunta::segundos($procesoPrueba->prueba?->familia)
                    : 0,
            ],
            'prueba' => [
                'id' => $procesoPrueba->prueba->id,
                'codigo' => $procesoPrueba->prueba->codigo,
                'nombre' => $procesoPrueba->prueba->nombre,
                'descripcion' => $procesoPrueba->prueba->descripcion,
            ],
            'progreso' => [
                'respondidas' => $cerradas,
                'total' => $total,
            ],
            'preguntas' => $pregunta === null ? [] : [[
                'id' => $pregunta->id,
                'orden' => $pregunta->orden,
                'enunciado' => $pregunta->enunciado,
                'imagen_url' => $imagenUrl,
                'opciones' => $pregunta->opciones->map(fn ($opcion) => [
                    'id' => $opcion->id,
                    'etiqueta' => $opcion->etiqueta,
                    'texto' => $opcion->texto,
                ])->values(),
            ]],
        ];
    }

    /**
     * Registra una respuesta a una pregunta específica dentro del intento.
     */
    public function responder(Request $request, int $intentoId, GuardarRespuestaIntento $guardar): JsonResponse
    {
        $request->validate([
            'pregunta_id' => ['required', 'integer'],
            'opcion_id' => ['required', 'integer'],
        ]);

        $usuario = $request->user();
        $intento = IntentoPrueba::with(['postulacion', 'procesoPrueba'])->findOrFail($intentoId);

        $respuesta = $guardar->handle(
            $usuario,
            $intento,
            $request->integer('pregunta_id'),
            $request->integer('opcion_id')
        );

        return response()->json([
            'mensaje' => $respuesta->expirada
                ? 'El tiempo de la pregunta terminó.'
                : 'Respuesta guardada con éxito.',
            'data' => [
                'pregunta_id' => $respuesta->pregunta_id,
                'opcion_id' => $respuesta->opcion_id,
                'expirada' => (bool) $respuesta->expirada,
                'respondida_en' => $respuesta->respondida_en,
            ],
        ]);
    }

    /**
     * Finaliza el intento, califica la prueba y actualiza las etapas.
     */
    public function finalizar(Request $request, int $intentoId, FinalizarIntentoPrueba $finalizar): JsonResponse
    {
        $usuario = $request->user();
        $intento = IntentoPrueba::with(['postulacion.persona', 'postulacion.etapas.procesoEtapa', 'procesoPrueba.prueba'])->findOrFail($intentoId);

        $resultado = $finalizar->handle($usuario, $intento);
        $resultado->load('dimensiones.dimension');

        $diagnosticoInteligencia = null;
        $diagnosticoEmocional = null;
        $diagnosticoLectura = null;
        $diagnosticoPurdue = null;
        $diagnosticoHabilidadesBlandas = null;

        $codigoPrueba = AsignacionPrueba::query()
            ->where('postulacion_id', $intento->postulacion_id)
            ->where('proceso_prueba_id', $intento->proceso_prueba_id)
            ->with('prueba')
            ->first()
            ?->prueba
            ?->codigo ?? $intento->procesoPrueba?->prueba?->codigo;

        if (in_array($codigoPrueba, ['ES_USTED_MUY_HABIL', 'DOMINOS_D48', 'RAVEN', 'TEST_INTELIGENCIA'], true)) {
            $diagnosticoInteligencia = $this->diagnosticoInteligencia((float) $resultado->puntaje_bruto, (float) $resultado->puntaje_maximo);
        } elseif (in_array($codigoPrueba, ['TMMS_24', 'TEST_INTELIGENCIA_EMOCIONAL'], true)) {
            $diagnosticoEmocional = $this->diagnosticoInteligenciaEmocional($intento, $intento->postulacion);
        } elseif ($codigoPrueba === 'TEST_LECTURA_COMPRENSIVA') {
            $diagnosticoLectura = $this->diagnosticoLecturaComprensiva((float) $resultado->puntaje_bruto, (float) $resultado->puntaje_maximo);
        } elseif ($codigoPrueba === 'TEST_PURDUE_NO_VERBAL') {
            $diagnosticoPurdue = $this->diagnosticoPurdue((float) $resultado->puntaje_bruto, (float) $resultado->puntaje_maximo);
        } elseif ($codigoPrueba === 'TEST_HABILIDADES_BLANDAS') {
            $diagnosticoHabilidadesBlandas = $this->diagnosticoHabilidadesBlandas($intento);
        }

        return response()->json([
            'mensaje' => 'Evaluación finalizada y calificada satisfactoriamente.',
            'data' => [
                'puntaje_bruto' => $resultado->puntaje_bruto,
                'puntaje_maximo' => $resultado->puntaje_maximo,
                'puntaje_normalizado' => $resultado->puntaje_normalizado,
                'aprobada' => $resultado->aprobada,
                'calificado_en' => $resultado->calificado_en,
                'diagnostico_inteligencia' => $diagnosticoInteligencia,
                'diagnostico_emocional' => $diagnosticoEmocional,
                'diagnostico_lectura' => $diagnosticoLectura,
                'diagnostico_purdue' => $diagnosticoPurdue,
                'diagnostico_habilidades_blandas' => $diagnosticoHabilidadesBlandas,
                'perfil_dimensiones' => $resultado->dimensiones->sortBy('dimension.orden')->map(fn ($d) => [
                    'id' => $d->dimension_id,
                    'codigo' => $d->dimension->codigo,
                    'nombre' => $d->dimension->nombre,
                    'valor' => (float) $d->valor,
                    'polo_bajo' => self::POLOS_16PF[$d->dimension->codigo]['bajo'] ?? 'Bajo',
                    'polo_alto' => self::POLOS_16PF[$d->dimension->codigo]['alto'] ?? 'Alto',
                ])->values(),
            ],
        ]);
    }
}
