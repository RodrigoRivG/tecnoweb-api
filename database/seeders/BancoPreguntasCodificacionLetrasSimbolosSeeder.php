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

/**
 * TEST I — Codificación letras-símbolos (100 elementos, clave A–J).
 * Láminas: TIPOS-DE-TEST/.../letra simbolos/ejercicios/ejercicio_NNN.png
 */
class BancoPreguntasCodificacionLetrasSimbolosSeeder extends Seeder
{
    /**
     * Claves por número de elemento.
     * Fuente: img.ejemplos (1–10), hoja de respuestas (11–40) y láminas img41-100.
     */
    private const CLAVES_OFICIALES = [
        1 => 'F', 2 => 'H', 3 => 'I', 4 => 'J', 5 => 'C', 6 => 'G', 7 => 'D', 8 => 'A', 9 => 'E', 10 => 'B',
        11 => 'G', 12 => 'E', 13 => 'D', 14 => 'H', 15 => 'I', 16 => 'F', 17 => 'C', 18 => 'J', 19 => 'B', 20 => 'A',
        21 => 'A', 22 => 'J', 23 => 'H', 24 => 'G', 25 => 'B', 26 => 'C', 27 => 'E', 28 => 'F', 29 => 'D', 30 => 'I',
        31 => 'C', 32 => 'I', 33 => 'F', 34 => 'E', 35 => 'J', 36 => 'H', 37 => 'G', 38 => 'A', 39 => 'B', 40 => 'D',
        41 => 'D', 42 => 'G', 43 => 'A', 44 => 'B', 45 => 'F', 46 => 'I', 47 => 'H', 48 => 'C', 49 => 'E', 50 => 'J',
        51 => 'E', 52 => 'F', 53 => 'J', 54 => 'I', 55 => 'C', 56 => 'H', 57 => 'A', 58 => 'D', 59 => 'G', 60 => 'B',
        61 => 'A', 62 => 'D', 63 => 'H', 64 => 'F', 65 => 'G', 66 => 'B', 67 => 'I', 68 => 'J', 69 => 'C', 70 => 'E',
        71 => 'B', 72 => 'E', 73 => 'I', 74 => 'C', 75 => 'A', 76 => 'J', 77 => 'H', 78 => 'G', 79 => 'D', 80 => 'F',
        81 => 'I', 82 => 'C', 83 => 'G', 84 => 'J', 85 => 'D', 86 => 'E', 87 => 'B', 88 => 'A', 89 => 'F', 90 => 'H',
        91 => 'J', 92 => 'D', 93 => 'E', 94 => 'I', 95 => 'B', 96 => 'A', 97 => 'F', 98 => 'C', 99 => 'H', 100 => 'G',
    ];

    private const ENUNCIADO = 'Elemento %d. Usando el código letra-símbolo (A–J) mostrado en la lámina, '
        . 'indique qué letra corresponde al símbolo del ejercicio.';

    /** @var list<string> */
    private const OPCIONES_LETRAS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'TEST_CODIFICACION')->firstOrFail();

        $this->command?->info("Poblando codificación letras-símbolos [{$prueba->codigo}] (ID: {$prueba->id})...");

        $this->sincronizarImagenesDesdeEscaneos();

        DB::transaction(function () use ($prueba): void {
            $preguntasIds = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $preguntasIds)->delete();

            Pregunta::query()->where('prueba_id', $prueba->id)->delete();

            $basePublicPath = public_path('pruebas/codificacion-letras-simbolos');

            for ($nro = 1; $nro <= 100; $nro++) {
                $nombreArchivo = "pregunta_{$nro}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/codificacion-letras-simbolos/{$nombreArchivo}";

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
                    $this->command?->warn("No se encontró {$rutaFisica}; el ítem {$nro} quedará sin imagen.");
                }

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'dimension_id' => null,
                    'orden' => $nro,
                    'enunciado' => sprintf(self::ENUNCIADO, $nro),
                    'imagen_archivo_id' => $archivoId,
                ]);

                $letraCorrecta = self::CLAVES_OFICIALES[$nro] ?? 'A';

                $opcionesData = [];
                foreach (self::OPCIONES_LETRAS as $letra) {
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => "Letra {$letra}",
                        'puntaje' => $letra === $letraCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("Codificación letras-símbolos: {$total} ítems cargados.");
    }

    private function sincronizarImagenesDesdeEscaneos(): void
    {
        $raizProyecto = dirname(base_path());
        $carpetaEjercicios = $raizProyecto . DIRECTORY_SEPARATOR . 'TIPOS-DE-TEST' . DIRECTORY_SEPARATOR
            . 'Habilidades-Aptitudes-específicas' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . '1'
            . DIRECTORY_SEPARATOR . 'letra simbolos' . DIRECTORY_SEPARATOR . 'ejercicios';
        $carpetaReferencia = dirname($carpetaEjercicios);

        $destino = public_path('pruebas/codificacion-letras-simbolos');
        File::ensureDirectoryExists($destino);

        $ejemplos = $carpetaReferencia . DIRECTORY_SEPARATOR . 'img.ejemplos.png';
        if (File::exists($ejemplos)) {
            File::copy($ejemplos, $destino . DIRECTORY_SEPARATOR . 'ejemplos.png');
        }

        $principal = $carpetaReferencia . DIRECTORY_SEPARATOR . 'img.principal.png';
        if (File::exists($principal)) {
            File::copy($principal, $destino . DIRECTORY_SEPARATOR . 'clave_principal.png');
        }

        for ($n = 1; $n <= 100; $n++) {
            $origen = $carpetaEjercicios . DIRECTORY_SEPARATOR . sprintf('ejercicio_%03d.png', $n);
            $destinoArchivo = $destino . DIRECTORY_SEPARATOR . "pregunta_{$n}.png";

            if (! File::exists($origen)) {
                $this->command?->warn("Escaneo faltante ítem {$n}: {$origen}");

                continue;
            }

            File::copy($origen, $destinoArchivo);
        }
    }
}