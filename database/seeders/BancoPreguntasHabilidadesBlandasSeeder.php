<?php

namespace Database\Seeders;

use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasHabilidadesBlandasSeeder extends Seeder
{
    /**
     * Dimensiones oficiales de Medición de Habilidades Blandas para talento profesional.
     */
    private const DIMENSIONES = [
        'COMUNICACION_ASERTIVA' => [
            'nombre' => 'Comunicación asertiva',
            'orden' => 20,
            'preguntas' => [
                'Expreso mis opiniones e ideas con claridad, seguridad y respeto, incluso cuando difieren de los demás.',
                'Escucho activamente y con empatía a mis compañeros antes de responder o emitir un juicio.',
                'Adapto mi lenguaje y estilo de comunicación de acuerdo al interlocutor y al contexto.',
                'Soy capaz de decir "no" de manera asertiva y justificada cuando una petición compromete objetivos prioritarios.',
                'Solicito y recibo retroalimentación constructiva de forma abierta sin tomarla como un ataque personal.',
            ],
        ],
        'TRABAJO_EN_EQUIPO' => [
            'nombre' => 'Trabajo en equipo',
            'orden' => 21,
            'preguntas' => [
                'Comparto voluntariamente mis conocimientos y recursos para facilitar el logro de las metas grupales.',
                'Priorizo el éxito y bienestar colectivo del equipo por encima del lucimiento individual.',
                'Muestro disposición activa para apoyar a colegas con alta carga laboral o que enfrentan dificultades.',
                'Construyo relaciones de confianza y respeto mutuo con compañeros de diversos perfiles y especialidades.',
                'Celebro genuinamente los logros de mis compañeros y reconozco su aporte en proyectos compartidos.',
            ],
        ],
        'RESOLUCION_CONFLICTOS' => [
            'nombre' => 'Resolución de conflictos',
            'orden' => 22,
            'preguntas' => [
                'Mantengo la calma y la objetividad frente a desacuerdos o discusiones tensas en el entorno laboral.',
                'Busco activamente soluciones "ganar-ganar" que satisfagan a las partes involucradas en una disputa.',
                'Abordo los desacuerdos directamente y con diplomacia en lugar de evadirlos o postergarlos.',
                'Identifico la causa raíz de un conflicto separando las emociones del problema técnico u operativo.',
                'Facilito el diálogo y actúo como mediador imparcial cuando surge tensión entre miembros del grupo.',
            ],
        ],
        'ADAPTABILIDAD' => [
            'nombre' => 'Adaptabilidad',
            'orden' => 23,
            'preguntas' => [
                'Acepto los cambios en planes o prioridades con rapidez y actitud positiva sin perder la motivación.',
                'Veo los errores y contratiempos como valiosas oportunidades de aprendizaje y mejora continua.',
                'Mantengo un desempeño eficaz y enfocado incluso cuando trabajo bajo presión o incertidumbre.',
                'Me adapto con agilidad al aprendizaje y adopción de nuevas herramientas digitales y metodologías de trabajo.',
                'Conservo el equilibrio emocional y la determinación frente a situaciones adversas o frustrantes.',
            ],
        ],
        'LIDERAZGO_INICIATIVA' => [
            'nombre' => 'Liderazgo',
            'orden' => 24,
            'preguntas' => [
                'Tomo la iniciativa para resolver problemas o proponer mejoras sin esperar que otros me lo indiquen.',
                'Inspiro y motivo a quienes me rodean para alcanzar altos estándares de calidad y compromiso.',
                'Asumo total responsabilidad sobre los resultados de mis decisiones y acciones.',
                'Anticipo posibles contingencias u obstáculos antes de que impacten el desarrollo de los proyectos.',
                'Guío y oriento con paciencia a compañeros que requieren tutoría o integración en el equipo.',
            ],
        ],
    ];

    /**
     * Escala Likert de 5 puntos (1 a 5).
     */
    private const OPCIONES_LIKERT = [
        ['etiqueta' => '1', 'texto' => 'Totalmente en desacuerdo', 'puntaje' => '1.00'],
        ['etiqueta' => '2', 'texto' => 'En desacuerdo', 'puntaje' => '2.00'],
        ['etiqueta' => '3', 'texto' => 'Neutral / A veces', 'puntaje' => '3.00'],
        ['etiqueta' => '4', 'texto' => 'De acuerdo', 'puntaje' => '4.00'],
        ['etiqueta' => '5', 'texto' => 'Totalmente de acuerdo', 'puntaje' => '5.00'],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_HABILIDADES_BLANDAS')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Asegurar la existencia de las 5 dimensiones en talento.dimension_evaluada
            $dimensionesIds = [];
            foreach (self::DIMENSIONES as $codigo => $datos) {
                $dim = DimensionEvaluada::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nombre' => $datos['nombre'],
                        'tipo' => 'HABILIDAD_BLANDA',
                        'orden' => $datos['orden'],
                    ]
                );
                $dimensionesIds[$codigo] = $dim->id;
            }

            // 2. Limpiar preguntas previas de esta prueba (en cascada limpia opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            // 3. Crear las 25 preguntas y sus opciones Likert
            $nroOrden = 1;
            foreach (self::DIMENSIONES as $codigoDim => $datos) {
                $dimId = $dimensionesIds[$codigoDim];

                foreach ($datos['preguntas'] as $enunciado) {
                    $pregunta = Pregunta::query()->create([
                        'prueba_id' => $prueba->id,
                        'tipo_prueba' => 'TEST',
                        'dimension_id' => $dimId,
                        'orden' => $nroOrden++,
                        'enunciado' => $enunciado,
                        'imagen_archivo_id' => null,
                    ]);

                    $opcionesData = [];
                    foreach (self::OPCIONES_LIKERT as $opc) {
                        $opcionesData[] = [
                            'pregunta_id' => $pregunta->id,
                            'etiqueta' => $opc['etiqueta'],
                            'texto' => $opc['texto'],
                            'puntaje' => $opc['puntaje'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    OpcionPregunta::query()->insert($opcionesData);
                }
            }
        });

        $totalPreguntas = Pregunta::where('prueba_id', $prueba->id)->count();
        $this->command?->info("¡Banco poblado con éxito! Se registraron {$totalPreguntas} preguntas de Habilidades Blandas.");
    }
}
