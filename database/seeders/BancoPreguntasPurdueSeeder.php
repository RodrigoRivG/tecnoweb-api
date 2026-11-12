<?php

namespace Database\Seeders;

use App\Models\Archivo;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BancoPreguntasPurdueSeeder extends Seeder
{
    /**
     * Claves oficiales del Test No Verbal de la Universidad de Purdue (Forma B).
     * Fuente: Escáner_20261026 (3).png (ítems 1 al 24) y Escáner_20261026 (5).png (ítems 25 al 48).
     * Plantillas perforadas originales autorizadas por SRA (Science Research Associates).
     */
    private const CLAVES_OFICIALES = [
        1  => 'C',
        2  => 'A',
        3  => 'D',
        4  => 'C',
        5  => 'C',
        6  => 'A',
        7  => 'C',
        8  => 'E',
        9  => 'C',
        10 => 'A',
        11 => 'A',
        12 => 'B',
        13 => 'C',
        14 => 'E',
        15 => 'C',
        16 => 'C',
        17 => 'E',
        18 => 'E',
        19 => 'D',
        20 => 'C',
        21 => 'E',
        22 => 'C',
        23 => 'C',
        24 => 'C',
        25 => 'E',
        26 => 'E',
        27 => 'E',
        28 => 'D',
        29 => 'E',
        30 => 'D',
        31 => 'A',
        32 => 'B',
        33 => 'D',
        34 => 'D',
        35 => 'D',
        36 => 'E',
        37 => 'A',
        38 => 'A',
        39 => 'A',
        40 => 'C',
        41 => 'E',
        42 => 'C',
        43 => 'D',
        44 => 'D',
        45 => 'D',
        46 => 'D',
        47 => 'B',
        48 => 'E',
    ];

    /**
     * Opciones estándar del test de Purdue (5 alternativas A - E por serie).
     */
    private const OPCIONES_LETRAS = ['A', 'B', 'C', 'D', 'E'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_PURDUE_NO_VERBAL')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Limpiar respuestas de intentos previos referidas a estas preguntas
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            // 2. Limpiar preguntas previas de esta prueba (en cascada limpia opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/purdue');

            for ($nro = 1; $nro <= 48; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/purdue/{$nombreArchivo}";

                $archivoId = null;
                if (File::exists($rutaFisica)) {
                    $tamano = File::size($rutaFisica);
                    $hash = hash_file('sha256', $rutaFisica);

                    $archivo = Archivo::query()->updateOrCreate(
                        ['clave_almacenamiento' => $claveAlmacenamiento],
                        [
                            'nombre_original' => $nombreArchivo,
                            'mime_type' => 'image/png',
                            'tamano_bytes' => $tamano,
                            'hash_sha256' => $hash,
                            'subido_por' => 1,
                        ]
                    );
                    $archivoId = $archivo->id;
                }

                // 3. Crear pregunta
                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => null,
                    'orden' => $nro,
                    'enunciado' => "En esta serie de 5 figuras geométricas, observe con atención y elija cuál de los 5 dibujos (A, B, C, D o E) es DIFERENTE de los otros cuatro.",
                    'imagen_archivo_id' => $archivoId,
                ]);

                // 4. Crear las 5 opciones de respuesta
                $letraCorrecta = self::CLAVES_OFICIALES[$nro] ?? 'A';

                $opcionesData = [];
                foreach (self::OPCIONES_LETRAS as $letra) {
                    $esCorrecta = ($letra === $letraCorrecta);
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => "Opción {$letra}",
                        'puntaje' => $esCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("¡Banco de preguntas de Purdue cargado exitosamente! Total de ítems registrados: {$total}.");
    }
}

