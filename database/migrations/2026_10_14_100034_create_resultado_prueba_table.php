<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talento.resultado_prueba', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->bigInteger('proceso_prueba_id');
            $table->bigInteger('intento_id')->nullable();
            $table->decimal('puntaje_bruto', 7, 2);
            $table->decimal('puntaje_maximo', 7, 2);
            // Calculado por la BD; ck_resultado_maximo garantiza que puntaje_maximo > 0
            $table->decimal('puntaje_normalizado', 5, 2)->nullable()->storedAs('ROUND(puntaje_bruto / puntaje_maximo * 100, 2)');
            $table->decimal('umbral_aplicado', 5, 2)->nullable();
            $table->boolean('aprobada');
            $table->string('motivo_no_aprobacion', 14)->nullable();
            $table->bigInteger('evaluador_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestampTz('calificado_en')->useCurrent();
            Talento::timestamps($table);

            $table->unique(['postulacion_id', 'proceso_prueba_id'], 'uq_resultado_postulacion_prueba');
            $table->unique(['id', 'postulacion_id', 'proceso_prueba_id'], 'uq_resultado_id_post_prueba');
            $table->foreign('postulacion_id', 'fk_resultado_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('proceso_prueba_id', 'fk_resultado_proceso_prueba')->references('id')->on('talento.proceso_prueba');
            $table->foreign('evaluador_id', 'fk_resultado_evaluador')->references('id')->on('talento.usuario');
            // El intento contado debe ser de la misma postulación y la misma prueba (FK compuesta)
            $table->foreign(['intento_id', 'postulacion_id', 'proceso_prueba_id'], 'fk_resultado_intento')
                ->references(['id', 'postulacion_id', 'proceso_prueba_id'])->on('talento.intento_prueba');
            $table->index('proceso_prueba_id', 'ix_resultado_proceso_prueba');
            $table->index('evaluador_id', 'ix_resultado_evaluador');
            $table->index('intento_id', 'ix_resultado_intento');
            $table->comment('Resultado final de una prueba para una postulación (test, rúbrica o calculada). Guarda el umbral con el que se juzgó');
        });

        Talento::finalizar('resultado_prueba', [
            'ck_resultado_motivo_valor' => Talento::en('motivo_no_aprobacion', ['PUNTAJE_BAJO', 'TIEMPO_MINIMO', 'TIEMPO_AGOTADO', 'OTRO']),
            'ck_resultado_maximo' => 'puntaje_maximo > 0',
            'ck_resultado_bruto' => 'puntaje_bruto >= 0 AND puntaje_bruto <= puntaje_maximo',
            'ck_resultado_motivo' => 'NOT aprobada OR motivo_no_aprobacion IS NULL',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.resultado_prueba');
    }
};
