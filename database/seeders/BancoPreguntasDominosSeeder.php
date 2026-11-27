<?php

namespace Database\Seeders;

use App\Models\Archivo;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\Prueba;
use Database\Seeders\Concerns\UsuarioSemillaSistema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Test D-48 (dominós): 48 láminas oficiales y clave de corrección.
 * Imágenes: public/pruebas/dominos-d48/pregunta_{1..48}.png
 * Claves: TIPOS-DE-TEST/inteligencia/1/ejercicios_recortados/repuesta.png
 */
class BancoPreguntasDominosSeeder extends Seeder
{
    /** Valor superior e inferior de la ficha correcta (puntos 0–6). */
    private const CLAVES_OFICIALES = [
        1 => [2, 4], 2 => [6, 1], 3 => [3, 5], 4 => [0, 2], 5 => [4, 1], 6 => [3, 6],
        7 => [5, 2], 8 => [0, 4], 9 => [2, 6], 10 => [3, 3], 11 => [4, 0], 12 => [5, 1],
        13 => [2, 3], 14 => [5, 6], 15 => [1, 4], 16 => [2, 5], 17 => [6, 0], 18 => [1, 4],
        19 => [2, 2], 20 => [1, 5], 21 => [0, 4], 22 => [6, 3], 23 => [2, 1], 24 => [3, 5],
        25 => [6, 4], 26 => [3, 0], 27 => [1, 5], 28 => [2, 4], 29 => [5, 5], 30 => [3, 6],
        31 => [5, 6], 32 => [4, 0], 33 => [4, 4], 34 => [1, 0], 35 => [6, 2], 36 => [3, 5],
        37 => [6, 0], 38 => [4, 6], 39 => [3, 5], 40 => [2, 1], 41 => [5, 1], 42 => [0, 6],
        43 => [4, 6], 44 => [3, 0], 45 => [2, 5], 46 => [5, 6], 47 => [2, 2], 48 => [1, 3],
    ];

    private const LETRAS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'DOMINOS_D48')->first();
        if ($prueba === null) {
            return;
        }

        $this->command?->info("Poblando dominós D-48 [{$prueba->codigo}] (ID: {$prueba->id})...");

        DB::transaction(function () use ($prueba): void {
            $this->vaciar($prueba->id);

            $basePublicPath = public_path('pruebas/dominos-d48');

            for ($n = 1; $n <= 48; $n++) {
                $nombreArchivo = "pregunta_{$n}.png";
                $rutaFisica = $basePublicPath . DIRECTORY_SEPARATOR . $nombreArchivo;
                $claveAlmacenamiento = "pruebas/dominos-d48/{$nombreArchivo}";

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
                    $this->command?->warn("Falta {$rutaFisica}; ítem {$n} sin imagen.");
                }

                $parejaCorrecta = self::CLAVES_OFICIALES[$n] ?? [0, 0];
                $textoCorrecta = $this->textoFicha($parejaCorrecta);

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'orden' => $n,
                    'enunciado' => "Ítem {$n}. Observe la serie de dominós en la lámina y determine qué ficha "
                        . 'completa el espacio vacío (mitad superior | mitad inferior). Elija una alternativa.',
                    'imagen_archivo_id' => $archivoId,
                ]);

                $opcionesData = [];
                foreach ($this->opcionesParaItem($n, $parejaCorrecta) as $indice => $pareja) {
                    $letra = self::LETRAS[$indice];
                    $texto = $this->textoFicha($pareja);
                    $opcionesData[] = [
                        'pregunta_id' => $pregunta->id,
                        'etiqueta' => $letra,
                        'texto' => $texto,
                        'puntaje' => $texto === $textoCorrecta ? '1.00' : '0.00',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                OpcionPregunta::query()->insert($opcionesData);
            }
        });

        $total = Pregunta::query()->where('prueba_id', $prueba->id)->count();
        $this->command?->info("Dominós D-48: {$total} ítems cargados con láminas.");
    }

    /**
     * @param  array{0: int, 1: int}  $correcta
     * @return list<array{0: int, 1: int}>
     */
    private function opcionesParaItem(int $n, array $correcta): array
    {
        $candidatas = [];
        for ($arriba = 0; $arriba <= 6; $arriba++) {
            for ($abajo = 0; $abajo <= 6; $abajo++) {
                if ($arriba !== $correcta[0] || $abajo !== $correcta[1]) {
                    $candidatas[] = [$arriba, $abajo];
                }
            }
        }

        mt_srand(10_000 + $n);
        shuffle($candidatas);

        $opciones = array_merge([$correcta], array_slice($candidatas, 0, 7));
        shuffle($opciones);

        return $opciones;
    }

    /**
     * @param  array{0: int, 1: int}  $pareja
     */
    private function textoFicha(array $pareja): string
    {
        return sprintf('[%d|%d]', $pareja[0], $pareja[1]);
    }

    private function vaciar(int $pruebaId): void
    {
        $ids = Pregunta::query()->where('prueba_id', $pruebaId)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
            Pregunta::query()->where('prueba_id', $pruebaId)->delete();
        }
    }
}