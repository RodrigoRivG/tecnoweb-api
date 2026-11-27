<?php

namespace Database\Seeders;

use App\Models\AsignacionPrueba;
use App\Models\Pregunta;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Retira el libro TMMS_24 del banco: las 24 afirmaciones viven en TEST_HABILIDADES_BLANDAS.
 */
class BancoPreguntasEmocionalSeeder extends Seeder
{
    public function run(): void
    {
        $legacy = Prueba::query()->where('codigo', 'TMMS_24')->first();
        $unificado = Prueba::query()->where('codigo', 'TEST_HABILIDADES_BLANDAS')->first();

        if ($legacy === null) {
            $this->command?->info('TMMS_24 ya no está en catálogo.');

            return;
        }

        if ($unificado === null) {
            $this->command?->warn('Falta TEST_HABILIDADES_BLANDAS; no se puede retirar TMMS_24.');

            return;
        }

        DB::transaction(function () use ($legacy, $unificado): void {
            ProcesoPrueba::query()
                ->where('prueba_id', $legacy->id)
                ->update(['prueba_id' => $unificado->id]);

            AsignacionPrueba::query()
                ->where('prueba_id', $legacy->id)
                ->update(['prueba_id' => $unificado->id]);

            $referenciada = DB::table('talento.respuesta_intento as r')
                ->join('talento.pregunta as p', 'r.pregunta_id', '=', 'p.id')
                ->where('p.prueba_id', $legacy->id)
                ->exists();

            if (! $referenciada) {
                $legacyPreguntaIds = Pregunta::query()->where('prueba_id', $legacy->id)->pluck('id');
                if ($legacyPreguntaIds->isNotEmpty()) {
                    DB::table('talento.respuesta_intento')->whereIn('pregunta_id', $legacyPreguntaIds)->delete();
                    Pregunta::query()->where('prueba_id', $legacy->id)->delete();
                }
                $legacy->delete();
                $this->command?->info('✓ Libro TMMS_24 eliminado del catálogo (ítems en Habilidades blandas).');
            } else {
                $legacy->update([
                    'activa' => false,
                    'es_plantilla' => false,
                    'nombre' => 'TMMS-24 (retirado)',
                    'descripcion' => 'Retirado: usar TEST_HABILIDADES_BLANDAS.',
                    'limite_preguntas' => 0,
                ]);
                $this->command?->warn('TMMS_24 conserva respuestas históricas; quedó inactivo y fuera del banco.');
            }
        });
    }
}