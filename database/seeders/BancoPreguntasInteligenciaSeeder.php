<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UsuarioSemillaSistema;
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
        $prueba = Prueba::query()->where('codigo', 'ES_USTED_MUY_HABIL')->firstOrFail();

        $this->command?->info("Poblando banco de preguntas para la prueba [{$prueba->codigo}] (ID: {$prueba->id})...");

        $this->sincronizarImagenesDesdeEscaneos();

        DB::transaction(function () use ($prueba) {
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/inteligencia');

            for ($nro = 1; $nro <= 15; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/inteligencia/{$nombreArchivo}";

                $archivoId = null;
                if (File::exists($rutaFisica)) {
                    $archivo = Archivo::query()->updateOrCreate(
                        ['clave_almacenamiento' => $claveAlmacenamiento],
                        [
                            'nombre_original' => $nombreArchivo,
                            'mime_type' => 'image/png',
                            'tamano_bytes' => File::size($rutaFisica),
                            'hash_sha256' => hash_file('sha256', $rutaFisica),
                            'subido_por' => UsuarioSemillaSistema::id(),
                        ]
                    );
                    $archivoId = $archivo->id;
                } else {
                    $this->command?->warn("No se encontró {$rutaFisica}; la pregunta {$nro} se creará sin imagen.");
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
        $this->command?->info("¿Es usted muy hábil?: {$total} matrices cargadas.");
    }

    private function sincronizarImagenesDesdeEscaneos(): void
    {
        $origen = dirname(base_path()) . DIRECTORY_SEPARATOR . 'TIPOS-DE-TEST' . DIRECTORY_SEPARATOR
            . 'inteligencia' . DIRECTORY_SEPARATOR . '2' . DIRECTORY_SEPARATOR . 'inteligencia_m';

        $destino = public_path('pruebas/inteligencia');
        File::ensureDirectoryExists($destino);

        for ($n = 1; $n <= 15; $n++) {
            $nombreOrigen = "pregunta_{$n}.png";
            $rutaOrigen = $origen . DIRECTORY_SEPARATOR . $nombreOrigen;
            $rutaDestino = $destino . DIRECTORY_SEPARATOR . $nombreOrigen;

            if (! File::exists($rutaOrigen)) {
                $this->command?->warn("Escaneo faltante para ítem {$n}: {$rutaOrigen}");

                continue;
            }

            File::copy($rutaOrigen, $rutaDestino);
        }
    }
}
