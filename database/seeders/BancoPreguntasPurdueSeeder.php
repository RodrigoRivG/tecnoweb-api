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

class BancoPreguntasPurdueSeeder extends Seeder
{
    /**
     * Claves oficiales — Test No Verbal Purdue, Forma A (48 series).
     * Fuente: Escáner_20261026 (8).png (ítems 1–24) y Escáner_20261026 (10).png (ítems 25–48).
     */
    private const CLAVES_OFICIALES = [
        1  => 'D',
        2  => 'D',
        3  => 'D',
        4  => 'B',
        5  => 'D',
        6  => 'D',
        7  => 'E',
        8  => 'E',
        9  => 'B',
        10 => 'C',
        11 => 'C',
        12 => 'D',
        13 => 'C',
        14 => 'B',
        15 => 'D',
        16 => 'D',
        17 => 'E',
        18 => 'B',
        19 => 'D',
        20 => 'A',
        21 => 'E',
        22 => 'C',
        23 => 'D',
        24 => 'D',
        25 => 'B',
        26 => 'E',
        27 => 'D',
        28 => 'C',
        29 => 'A',
        30 => 'D',
        31 => 'E',
        32 => 'C',
        33 => 'D',
        34 => 'E',
        35 => 'C',
        36 => 'D',
        37 => 'A',
        38 => 'A',
        39 => 'C',
        40 => 'D',
        41 => 'B',
        42 => 'C',
        43 => 'E',
        44 => 'C',
        45 => 'D',
        46 => 'B',
        47 => 'D',
        48 => 'C',
    ];

    private const ENUNCIADO_SERIE = 'En cada serie de 5 dibujos hay uno que es diferente de los demás. '
        . 'Elija la opción (A, B, C, D o E) que corresponde a ese dibujo distinto.';

    /** @var list<string> */
    private const OPCIONES_LETRAS = ['A', 'B', 'C', 'D', 'E'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_PURDUE_NO_VERBAL')->firstOrFail();

        $this->command?->info("Poblando banco Purdue Forma A [{$prueba->codigo}] (ID: {$prueba->id})...");

        $this->sincronizarImagenesDesdeEscaneos();

        DB::transaction(function () use ($prueba): void {
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/purdue');

            for ($nro = 1; $nro <= 48; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/purdue/{$nombreArchivo}";

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

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => null,
                    'orden' => $nro,
                    'enunciado' => self::ENUNCIADO_SERIE,
                    'imagen_archivo_id' => $archivoId,
                ]);

                $letraCorrecta = self::CLAVES_OFICIALES[$nro] ?? 'A';

                $opcionesData = [];
                foreach (self::OPCIONES_LETRAS as $letra) {
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => "Opción {$letra}",
                        'puntaje' => $letra === $letraCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("Purdue Forma A: {$total} ítems cargados en el banco.");
    }

    /**
     * Copia escaneos del monorepo (TIPOS-DE-TEST) a public/pruebas/purdue para servir las láminas.
     */
    private function sincronizarImagenesDesdeEscaneos(): void
    {
        $raizProyecto = dirname(base_path());
        $carpetaFormaA = $raizProyecto . DIRECTORY_SEPARATOR . 'TIPOS-DE-TEST' . DIRECTORY_SEPARATOR
            . 'inteligencia' . DIRECTORY_SEPARATOR . '3' . DIRECTORY_SEPARATOR . 'forma a';
        $carpetaFormaA2 = $carpetaFormaA . DIRECTORY_SEPARATOR . 'forma a -2';

        $destino = public_path('pruebas/purdue');
        File::ensureDirectoryExists($destino);

        $instrucciones = $carpetaFormaA . DIRECTORY_SEPARATOR . 'Escáner_20261026 (6).png';
        if (File::exists($instrucciones)) {
            File::copy($instrucciones, $destino . DIRECTORY_SEPARATOR . 'instrucciones_forma_a.png');
        }

        for ($n = 1; $n <= 24; $n++) {
            $origen = $carpetaFormaA . DIRECTORY_SEPARATOR . "escaner-20261026-(7)-{$n}.png";
            $this->copiarSiExiste($origen, $destino . DIRECTORY_SEPARATOR . "pregunta_{$n}.png", $n);
        }

        for ($n = 25; $n <= 48; $n++) {
            $origen = $carpetaFormaA2 . DIRECTORY_SEPARATOR . "escaner-20261026-(9)-{$n}.png";
            $this->copiarSiExiste($origen, $destino . DIRECTORY_SEPARATOR . "pregunta_{$n}.png", $n);
        }
    }

    private function copiarSiExiste(string $origen, string $destino, int $numeroItem): void
    {
        if (! File::exists($origen)) {
            $this->command?->warn("Escaneo faltante para ítem {$numeroItem}: {$origen}");

            return;
        }

        File::copy($origen, $destino);
    }
}