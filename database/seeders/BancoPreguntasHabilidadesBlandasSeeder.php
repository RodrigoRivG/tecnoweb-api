<?php

namespace Database\Seeders;

use App\Catalogos\ItemsTmms24;
use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasHabilidadesBlandasSeeder extends Seeder
{
    /**
     * Test único: TMMS-24 (3 dimensiones) + competencias socioemocionales (5 dimensiones) = 8 y 49 ítems.
     */
    private const DIMENSIONES = [
        'ATENCION_EMOCIONAL' => [
            'nombre' => 'Atención emocional',
            'orden' => 17,
            'preguntas' => [],
        ],
        'CLARIDAD_EMOCIONAL' => [
            'nombre' => 'Claridad emocional',
            'orden' => 18,
            'preguntas' => [],
        ],
        'REPARACION_EMOCIONAL' => [
            'nombre' => 'Reparación emocional',
            'orden' => 19,
            'preguntas' => [],
        ],
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

    private const OPCIONES_LIKERT = [
        ['etiqueta' => '1', 'texto' => 'Totalmente en desacuerdo', 'puntaje' => '1.00'],
        ['etiqueta' => '2', 'texto' => 'En desacuerdo', 'puntaje' => '2.00'],
        ['etiqueta' => '3', 'texto' => 'Neutral / A veces', 'puntaje' => '3.00'],
        ['etiqueta' => '4', 'texto' => 'De acuerdo', 'puntaje' => '4.00'],
        ['etiqueta' => '5', 'texto' => 'Totalmente de acuerdo', 'puntaje' => '5.00'],
    ];

    /**
     * Escala TMMS (misma redacción que el instrumento original).
     */
    private const OPCIONES_LIKERT_TMMS = [
        ['etiqueta' => '1', 'texto' => 'Nada de acuerdo', 'puntaje' => '1.00'],
        ['etiqueta' => '2', 'texto' => 'Algo de acuerdo', 'puntaje' => '2.00'],
        ['etiqueta' => '3', 'texto' => 'Bastante de acuerdo', 'puntaje' => '3.00'],
        ['etiqueta' => '4', 'texto' => 'Muy de acuerdo', 'puntaje' => '4.00'],
        ['etiqueta' => '5', 'texto' => 'Totalmente de acuerdo', 'puntaje' => '5.00'],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_HABILIDADES_BLANDAS')->firstOrFail();

        $this->command?->info("Poblando Habilidades blandas [{$prueba->codigo}] (ID: {$prueba->id})…");

        DB::transaction(function () use ($prueba): void {
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

            $totalEsperado = 49;
            $actual = Pregunta::query()->where('prueba_id', $prueba->id)->count();
            $referenciada = DB::table('talento.respuesta_intento as r')
                ->join('talento.pregunta as p', 'r.pregunta_id', '=', 'p.id')
                ->where('p.prueba_id', $prueba->id)
                ->exists();

            if ($actual === $totalEsperado) {
                $this->actualizarMetadatosPrueba($prueba);

                return;
            }

            if ($referenciada && $actual === 25) {
                Pregunta::query()
                    ->where('prueba_id', $prueba->id)
                    ->orderByDesc('orden')
                    ->each(fn (Pregunta $pregunta) => $pregunta->update(['orden' => $pregunta->orden + 24]));

                $nroOrden = 1;
                foreach (ItemsTmms24::afirmaciones() as $item) {
                    $this->crearPreguntaLikert(
                        $prueba->id,
                        $dimensionesIds[$item['dimension']],
                        $nroOrden++,
                        $item['texto'],
                        self::OPCIONES_LIKERT_TMMS,
                    );
                }

                $this->actualizarMetadatosPrueba($prueba);
                $this->command?->info('✓ Se antepusieron 24 ítems TMMS al banco existente (25 → 49).');

                return;
            }

            if ($referenciada) {
                $this->command?->warn("Hay respuestas en el banco ({$actual} ítems); no se borra. En desarrollo: migrate:fresh --seed o vaciar intentos antes de resembrar.");

                return;
            }

            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            if ($preguntasIds->isNotEmpty()) {
                DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();
                Pregunta::query()->where('prueba_id', $prueba->id)->delete();
            }

            $nroOrden = 1;

            foreach (ItemsTmms24::afirmaciones() as $item) {
                $this->crearPreguntaLikert(
                    $prueba->id,
                    $dimensionesIds[$item['dimension']],
                    $nroOrden++,
                    $item['texto'],
                    self::OPCIONES_LIKERT_TMMS,
                );
            }

            foreach (self::DIMENSIONES as $codigoDim => $datos) {
                if ($datos['preguntas'] === []) {
                    continue;
                }

                $dimId = $dimensionesIds[$codigoDim];
                foreach ($datos['preguntas'] as $enunciado) {
                    $this->crearPreguntaLikert(
                        $prueba->id,
                        $dimId,
                        $nroOrden++,
                        $enunciado,
                        self::OPCIONES_LIKERT,
                    );
                }
            }

            $this->actualizarMetadatosPrueba($prueba);
        });

        $totalPreguntas = Pregunta::where('prueba_id', $prueba->id)->count();
        $this->command?->info("✓ {$totalPreguntas} preguntas (8 dimensiones) en TEST_HABILIDADES_BLANDAS.");
    }

    /**
     * @param  list<array{etiqueta: string, texto: string, puntaje: string}>  $opciones
     */
    private function crearPreguntaLikert(int $pruebaId, int $dimensionId, int $orden, string $enunciado, array $opciones): void
    {
        $pregunta = Pregunta::query()->create([
            'prueba_id' => $pruebaId,
            'tipo_prueba' => 'TEST',
            'dimension_id' => $dimensionId,
            'orden' => $orden,
            'enunciado' => $enunciado,
            'imagen_archivo_id' => null,
        ]);

        $opcionesData = [];
        foreach ($opciones as $opc) {
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

    private function actualizarMetadatosPrueba(Prueba $prueba): void
    {
        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $prueba->update([
            'nombre' => 'Habilidades blandas',
            'descripcion' => 'Cuestionario de 49 preguntas en 8 dimensiones (inteligencia emocional y competencias socioemocionales).',
            'tiempo_limite_seg' => 2100,
            'limite_preguntas' => $total,
            'activa' => true,
        ]);
    }
}