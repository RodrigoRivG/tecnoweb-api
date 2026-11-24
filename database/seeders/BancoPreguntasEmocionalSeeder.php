<?php

namespace Database\Seeders;

use App\Models\DimensionEvaluada;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancoPreguntasEmocionalSeeder extends Seeder
{
    /**
     * Afirmaciones oficiales del Trait Meta-Mood Scale (TMMS-24) de Salovey, Mayer y Fernández-Berrocal.
     * Fuente: TMMS24-copia-2_lj1NJJp.pdf
     */
    private const PREGUNTAS_TMMS24 = [
        // Factor 1: Atención Emocional (ítems 1 al 8)
        1 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Presto mucha atención a los sentimientos.'],
        2 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Normalmente me preocupo mucho por lo que siento.'],
        3 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Normalmente dedico tiempo a pensar en mis emociones.'],
        4 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Pienso que merece la pena prestar atención a mis emociones y estado de ánimo.'],
        5 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Dejo que mis sentimientos afecten a mis pensamientos.'],
        6 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Pienso en mi estado de ánimo constantemente.'],
        7 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'A menudo pienso en mis sentimientos.'],
        8 => ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Presto mucha atención a cómo me siento.'],

        // Factor 2: Claridad de Sentimientos (ítems 9 al 16)
        9 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Tengo claros mis sentimientos.'],
        10 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Frecuentemente puedo definir mis sentimientos.'],
        11 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Casi siempre sé cómo me siento.'],
        12 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Normalmente conozco mis sentimientos sobre las personas.'],
        13 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'A menudo me doy cuenta de mis sentimientos en diferentes situaciones.'],
        14 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Siempre puedo decir cómo me siento.'],
        15 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'A veces puedo decir cuáles son mis emociones.'],
        16 => ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Puedo llegar a comprender mis sentimientos.'],

        // Factor 3: Reparación Emocional (ítems 17 al 24)
        17 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Aunque a veces me siento triste, suelo tener una visión optimista.'],
        18 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Aunque me sienta mal, procuro pensar en cosas agradables.'],
        19 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Cuando estoy triste, pienso en todos los placeres de la vida.'],
        20 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Intento tener pensamientos positivos aunque me sienta mal.'],
        21 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Si doy demasiadas vueltas a las cosas, complicándolas, trato de calmarme.'],
        22 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Me preocupo por tener un buen estado de ánimo.'],
        23 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Tengo mucha energía cuando me siento feliz.'],
        24 => ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Cuando estoy enfadado intento cambiar mi estado de ánimo.'],
    ];

    /**
     * Opciones estándar de la escala Likert (1 a 5).
     */
    private const OPCIONES_LIKERT = [
        ['etiqueta' => '1', 'texto' => 'Nada de acuerdo', 'puntaje' => '1.00'],
        ['etiqueta' => '2', 'texto' => 'Algo de acuerdo', 'puntaje' => '2.00'],
        ['etiqueta' => '3', 'texto' => 'Bastante de acuerdo', 'puntaje' => '3.00'],
        ['etiqueta' => '4', 'texto' => 'Muy de acuerdo', 'puntaje' => '4.00'],
        ['etiqueta' => '5', 'texto' => 'Totalmente de acuerdo', 'puntaje' => '5.00'],
    ];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TMMS_24')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Asegurar la existencia de las 3 dimensiones emocionales en talento.dimension_evaluada
            $dimensiones = [
                'ATENCION_EMOCIONAL' => ['nombre' => 'Atención emocional', 'tipo' => 'HABILIDAD_BLANDA', 'orden' => 17],
                'CLARIDAD_EMOCIONAL' => ['nombre' => 'Claridad emocional', 'tipo' => 'HABILIDAD_BLANDA', 'orden' => 18],
                'REPARACION_EMOCIONAL' => ['nombre' => 'Reparación emocional', 'tipo' => 'HABILIDAD_BLANDA', 'orden' => 19],
            ];

            $mapaDimensiones = [];
            foreach ($dimensiones as $codigo => $datos) {
                $dim = DimensionEvaluada::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nombre' => $datos['nombre'],
                        'tipo' => $datos['tipo'],
                        'orden' => $datos['orden'],
                    ]
                );
                $mapaDimensiones[$codigo] = $dim->id;
            }

            // 2. Limpiar preguntas previas de esta prueba (en cascada limpia opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            // 3. Crear las 24 preguntas y sus 5 opciones Likert
            foreach (self::PREGUNTAS_TMMS24 as $orden => $datosItem) {
                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => $mapaDimensiones[$datosItem['dimension']] ?? null,
                    'orden' => $orden,
                    'enunciado' => $datosItem['texto'],
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
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("✓ Se registraron exitosamente {$total} afirmaciones con escala Likert (1-5) para el Test de Inteligencia Emocional (TMMS-24).");
    }
}
