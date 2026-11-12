<?php

namespace Database\Seeders;

use App\Models\Archivo;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BancoPreguntasInteligenciaSeeder extends Seeder
{
    /**
     * Claves oficiales del test psicométrico de Matrices de Figuras ("¿Es usted muy hábil?").
     * Fuente: Escáner_20261026 (18).png - Clave Matriz de Evaluación Psicométrica.
     */
    private const CLAVES_OFICIALES = [
        1 => 'E',
        2 => 'E',
        3 => 'B',
        4 => 'E',
        5 => 'G',
        6 => 'H',
        7 => 'C',
        8 => 'H',
        9 => 'G',
        10 => 'D',
        11 => 'F',
        12 => 'A',
        13 => 'G',
        14 => 'F',
        15 => 'E',
    ];

    /**
     * Opciones estándar del test de matrices (8 alternativas por matriz).
     */
    private const OPCIONES_LETRAS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_INTELIGENCIA')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba) {
            // 1. Limpiar preguntas previas de esta prueba (en cascada limpia opciones)
            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/inteligencia');

            for ($nro = 1; $nro <= 15; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/inteligencia/{$nombreArchivo}";

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

                // 2. Crear pregunta
                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => null,
                    'orden' => $nro,
                    'enunciado' => "Observe con atención el patrón lógico de la matriz de 3x3 y seleccione cuál de los 8 mosaicos (A - H) corresponde en la casilla faltante.",
                    'imagen_archivo_id' => $archivoId,
                ]);

                // 3. Crear las 8 opciones de respuesta
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
        $this->command?->info("✓ Se registraron exitosamente {$total} preguntas con sus 8 alternativas para el Test de Inteligencia.");
    }
}
