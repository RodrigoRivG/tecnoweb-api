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

class BancoPreguntasPurdueFormaBSeeder extends Seeder
{
    /**
     * Claves oficiales — Test No Verbal Purdue, Forma B (48 series).
     * Fuente: Escáner_20261026 (3).png (ítems 1–24) y Escáner_20261026 (5).png (ítems 25–48).
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

    private const ENUNCIADO_SERIE = 'En cada serie de 5 dibujos hay uno que es diferente de los demás. '
        . 'Elija la opción (A, B, C, D o E) que corresponde a ese dibujo distinto.';

    /** @var list<string> */
    private const OPCIONES_LETRAS = ['A', 'B', 'C', 'D', 'E'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_PURDUE_NO_VERBAL_FORMA_B')->firstOrFail();

        $this->command?->info("Poblando banco Purdue Forma B [{$prueba->codigo}] (ID: {$prueba->id})...");

        $this->sincronizarImagenesDesdeEscaneos();

        DB::transaction(function () use ($prueba): void {
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/purdue-forma-b');

            for ($nro = 1; $nro <= 48; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/purdue-forma-b/{$nombreArchivo}";

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
        $this->command?->info("Purdue Forma B: {$total} ítems cargados en el banco.");
    }

    private function sincronizarImagenesDesdeEscaneos(): void
    {
        $raizProyecto = dirname(base_path());
        $carpetaFormaB = $raizProyecto . DIRECTORY_SEPARATOR . 'TIPOS-DE-TEST' . DIRECTORY_SEPARATOR
            . 'inteligencia' . DIRECTORY_SEPARATOR . '3' . DIRECTORY_SEPARATOR . 'forma b';
        $carpetaFormaB2 = $carpetaFormaB . DIRECTORY_SEPARATOR . 'forma b-2';

        $destino = public_path('pruebas/purdue-forma-b');
        File::ensureDirectoryExists($destino);

        $instrucciones = $carpetaFormaB . DIRECTORY_SEPARATOR . 'Escáner_20261026 (1).png';
        if (File::exists($instrucciones)) {
            File::copy($instrucciones, $destino . DIRECTORY_SEPARATOR . 'instrucciones_forma_b.png');
        }

        for ($n = 1; $n <= 24; $n++) {
            $origen = $carpetaFormaB . DIRECTORY_SEPARATOR . "escaner-20261026-(2)-{$n}.png";
            $this->copiarSiExiste($origen, $destino . DIRECTORY_SEPARATOR . "pregunta_{$n}.png", $n);
        }

        for ($n = 25; $n <= 48; $n++) {
            $origen = $carpetaFormaB2 . DIRECTORY_SEPARATOR . "escaner-20261026-(4)-{$n}.png";
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