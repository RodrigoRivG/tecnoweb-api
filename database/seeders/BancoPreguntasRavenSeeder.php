<?php

namespace Database\Seeders;

use App\Models\Archivo;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Matrices progresivas (Raven, escala estándar): 5 series × 12 ítems, 60 láminas.
 * Láminas: TIPOS-DE-TEST/inteligencia/4/ejercicios_recortados (A01–E12).
 * Claves de respuesta según manual normativo del test (series A–B: 6 alternativas; C–E: 8).
 */
class BancoPreguntasRavenSeeder extends Seeder
{
    /** Respuesta correcta como número impreso en la lámina (1–6 o 1–8). */
    private const CLAVES_NUMERO = [
        1 => 4, 2 => 5, 3 => 1, 4 => 2, 5 => 6, 6 => 3, 7 => 6, 8 => 2, 9 => 1, 10 => 3, 11 => 6, 12 => 4,
        13 => 2, 14 => 6, 15 => 1, 16 => 2, 17 => 1, 18 => 3, 19 => 5, 20 => 6, 21 => 4, 22 => 3, 23 => 4, 24 => 5,
        25 => 8, 26 => 2, 27 => 3, 28 => 8, 29 => 7, 30 => 4, 31 => 5, 32 => 1, 33 => 7, 34 => 6, 35 => 1, 36 => 2,
        37 => 3, 38 => 4, 39 => 3, 40 => 7, 41 => 8, 42 => 6, 43 => 2, 44 => 3, 45 => 4, 46 => 5, 47 => 1, 48 => 7,
        49 => 7, 50 => 6, 51 => 8, 52 => 2, 53 => 1, 54 => 5, 55 => 6, 56 => 2, 57 => 1, 58 => 5, 59 => 4, 60 => 6,
    ];

    /** @var list<string> */
    private const SERIES = ['A', 'B', 'C', 'D', 'E'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'RAVEN')->first();
        if ($prueba === null) {
            return;
        }

        $this->command?->info("Poblando matrices progresivas [{$prueba->codigo}] (ID: {$prueba->id})...");

        $this->sincronizarImagenesDesdeEscaneos();

        DB::transaction(function () use ($prueba): void {
            $ids = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
                Pregunta::query()->where('prueba_id', $prueba->id)->delete();
            }

            $basePublicPath = public_path('pruebas/raven');

            for ($n = 1; $n <= 60; $n++) {
                $nombreArchivo = "pregunta_{$n}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/raven/{$nombreArchivo}";

                $archivoId = null;
                if (File::exists($rutaFisica)) {
                    $archivo = Archivo::query()->updateOrCreate(
                        ['clave_almacenamiento' => $claveAlmacenamiento],
                        [
                            'nombre_original' => $nombreArchivo,
                            'mime_type' => 'image/png',
                            'tamano_bytes' => File::size($rutaFisica),
                            'hash_sha256' => hash_file('sha256', $rutaFisica),
                            'subido_por' => 1,
                        ]
                    );
                    $archivoId = $archivo->id;
                } else {
                    $this->command?->warn("Falta {$rutaFisica}; ítem {$n} sin imagen.");
                }

                $serieLetra = self::SERIES[intdiv($n - 1, 12)];
                $itemSerie = (($n - 1) % 12) + 1;

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'orden' => $n,
                    'enunciado' => "Serie {$serieLetra}, ítem {$itemSerie}. Observe la lámina: la figura con pieza faltante "
                        . 'y los números debajo. Elija la letra que coincide con el número correcto (A = 1, B = 2, etc.).',
                    'imagen_archivo_id' => $archivoId,
                ]);

                $numeroCorrecto = self::CLAVES_NUMERO[$n] ?? 1;
                $letraCorrecta = chr(64 + $numeroCorrecto);
                $cantidadOpciones = $n <= 24 ? 6 : 8;

                $opcionesData = [];
                for ($i = 1; $i <= $cantidadOpciones; $i++) {
                    $letra = chr(64 + $i);
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => "Opción {$letra} (número {$i} en la lámina)",
                        'puntaje' => $letra === $letraCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("Matrices progresivas: {$total} ítems cargados.");
    }

    private function sincronizarImagenesDesdeEscaneos(): void
    {
        $origen = dirname(base_path()) . DIRECTORY_SEPARATOR . 'TIPOS-DE-TEST' . DIRECTORY_SEPARATOR
            . 'inteligencia' . DIRECTORY_SEPARATOR . '4' . DIRECTORY_SEPARATOR . 'ejercicios_recortados';

        $destino = public_path('pruebas/raven');
        File::ensureDirectoryExists($destino);

        foreach (self::SERIES as $indiceSerie => $letra) {
            for ($item = 1; $item <= 12; $item++) {
                $n = $indiceSerie * 12 + $item;
                $nombreOrigen = $letra . str_pad((string) $item, 2, '0', STR_PAD_LEFT) . '.png';
                $rutaOrigen = $origen . DIRECTORY_SEPARATOR . $nombreOrigen;

                if (! File::exists($rutaOrigen)) {
                    $this->command?->warn("Escaneo faltante ítem {$n}: {$rutaOrigen}");

                    continue;
                }

                File::copy($rutaOrigen, $destino . DIRECTORY_SEPARATOR . "pregunta_{$n}.png");
            }
        }
    }
}