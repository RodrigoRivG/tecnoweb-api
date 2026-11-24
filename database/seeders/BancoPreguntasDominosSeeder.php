<?php

namespace Database\Seeders;

use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 48 series propias de fichas. No reproduce las láminas comerciales del D-48.
 */
class BancoPreguntasDominosSeeder extends Seeder
{
    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'DOMINOS_D48')->first();
        if ($prueba === null) {
            return;
        }

        DB::transaction(function () use ($prueba): void {
            $this->vaciar($prueba->id);

            for ($n = 1; $n <= 48; $n++) {
                $serie = $this->serie($n);
                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'orden' => $n,
                    'enunciado' => "Serie {$n}. Cada ficha se escribe [izquierda|derecha]. La secuencia es {$serie['texto']}. ¿Qué ficha sigue?",
                ]);

                $letras = ['A', 'B', 'C', 'D'];
                foreach ($serie['opciones'] as $indice => $texto) {
                    $pregunta->opciones()->create([
                        'etiqueta' => $letras[$indice],
                        'texto' => $texto,
                        'puntaje' => $texto === $serie['correcta'] ? 1 : 0,
                    ]);
                }
            }
        });

        $this->command?->info('Dominós: 48 series cargadas.');
    }

    /**
     * @return array{texto: string, correcta: string, opciones: list<string>}
     */
    private function serie(int $n): array
    {
        $paso = ($n % 3) + 1;
        $inicio = $n % 7;
        $fichas = [];
        $izquierda = $inicio;
        $derecha = ($inicio + $paso) % 7;

        for ($i = 0; $i < 4; $i++) {
            $fichas[] = sprintf('[%d|%d]', $izquierda, $derecha);
            $izquierda = $derecha;
            $derecha = ($derecha + $paso) % 7;
        }

        $correcta = sprintf('[%d|%d]', $izquierda, $derecha);
        $distractores = [
            sprintf('[%d|%d]', $izquierda, ($derecha + 1) % 7),
            sprintf('[%d|%d]', ($izquierda + 1) % 7, $derecha),
            sprintf('[%d|%d]', $derecha, $izquierda),
        ];

        $opciones = $distractores;
        array_splice($opciones, $n % 4, 0, [$correcta]);

        return [
            'texto' => implode(' ', array_slice($fichas, 0, 4)),
            'correcta' => $correcta,
            'opciones' => $opciones,
        ];
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
