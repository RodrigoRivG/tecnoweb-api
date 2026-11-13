<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prueba = DB::table('talento.prueba')->where('codigo', 'TEST_HERRAMIENTAS')->first();
        if (!$prueba) {
            return;
        }

        DB::transaction(function () use ($prueba) {
            $procesoPruebaIds = DB::table('talento.proceso_prueba')
                ->where('prueba_id', $prueba->id)
                ->pluck('id');

            $preguntaIds = DB::table('talento.pregunta')
                ->where('prueba_id', $prueba->id)
                ->pluck('id');

            // 1. Eliminar respuestas a las preguntas de esta prueba
            DB::table('talento.respuesta_intento')
                ->whereIn('pregunta_id', $preguntaIds)
                ->delete();

            // 2. Eliminar resultados de proceso_prueba asociados
            DB::table('talento.resultado_prueba')
                ->whereIn('proceso_prueba_id', $procesoPruebaIds)
                ->delete();

            // 3. Eliminar intentos asociados
            DB::table('talento.intento_prueba')
                ->whereIn('proceso_prueba_id', $procesoPruebaIds)
                ->delete();

            // 4. Eliminar configuración de la prueba en procesos (proceso_prueba)
            DB::table('talento.proceso_prueba')
                ->whereIn('id', $procesoPruebaIds)
                ->delete();

            // 5. Eliminar opciones de preguntas
            DB::table('talento.opcion_pregunta')
                ->whereIn('pregunta_id', $preguntaIds)
                ->delete();

            // 6. Eliminar preguntas
            DB::table('talento.pregunta')
                ->whereIn('id', $preguntaIds)
                ->delete();

            // 7. Eliminar rúbricas si existieran
            DB::table('talento.rubrica_criterio')
                ->where('prueba_id', $prueba->id)
                ->delete();

            // 8. Eliminar el registro de la prueba
            DB::table('talento.prueba')
                ->where('id', $prueba->id)
                ->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible automáticamente tras eliminar preguntas e histórico de respuestas.
    }
};
