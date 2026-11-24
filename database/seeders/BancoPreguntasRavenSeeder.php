<?php

namespace Database\Seeders;

use App\Models\Pregunta;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 60 matrices propias en 5 series de 12. No reproduce las láminas de Raven.
 */
class BancoPreguntasRavenSeeder extends Seeder
{
    public function run(): void
    {
        $prueba = Prueba::query()->where('codigo', 'RAVEN')->first();
        if ($prueba === null) {
            return;
        }

        DB::transaction(function () use ($prueba): void {
            $ids = Pregunta::query()->where('prueba_id', $prueba->id)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $ids)->delete();
                Pregunta::query()->where('prueba_id', $prueba->id)->delete();
            }

            for ($n = 1; $n <= 60; $n++) {
                $serie = intdiv($n - 1, 12) + 1;
                $dentro = (($n - 1) % 12) + 1;
                $paso = $serie;
                $base = $dentro;
                $celdas = [];
                for ($fila = 0; $fila < 3; $fila++) {
                    $linea = [];
                    for ($columna = 0; $columna < 3; $columna++) {
                        $valor = $base + ($fila * 3 + $columna) * $paso;
                        $linea[] = ($fila === 2 && $columna === 2) ? '?' : (string) $valor;
                    }
                    $celdas[] = implode('   ', $linea);
                }
                $correcta = (string) ($base + 8 * $paso);
                $opciones = $this->opciones($correcta, $paso, $n);

                $pregunta = Pregunta::query()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_prueba' => 'TEST',
                    'orden' => $n,
                    'enunciado' => "Serie ".chr(64 + $serie).", ítem {$dentro}. La matriz avanza de a {$paso}.\n".implode("\n", $celdas)."\n¿Qué número ocupa la casilla marcada con ??",
                ]);

                foreach ($opciones as $indice => $texto) {
                    $pregunta->opciones()->create([
                        'etiqueta' => chr(65 + $indice),
                        'texto' => $texto,
                        'puntaje' => $texto === $correcta ? 1 : 0,
                    ]);
                }
            }
        });

        $this->command?->info('Matrices: 60 ítems en 5 series.');
    }

    /**
     * @return list<string>
     */
    private function opciones(string $correcta, int $paso, int $n): array
    {
        $numero = (int) $correcta;
        $distractores = [
            (string) ($numero + $paso),
            (string) ($numero - $paso),
            (string) ($numero + 1),
            (string) ($numero - 1),
            (string) ($numero + $paso + 1),
        ];
        $opciones = array_values(array_unique($distractores));
        $opciones = array_slice($opciones, 0, 5);
        array_splice($opciones, $n % 6, 0, [$correcta]);

        return array_slice($opciones, 0, 6);
    }
}
