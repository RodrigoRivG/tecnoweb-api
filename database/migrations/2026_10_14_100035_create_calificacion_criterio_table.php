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
        Schema::create('talento.calificacion_criterio', function (Blueprint $table) {
            $table->bigInteger('resultado_prueba_id');
            $table->integer('criterio_id');
            $table->decimal('puntaje', 5, 2);
            $table->string('comentario', 500)->nullable();
            Talento::timestamps($table);

            $table->primary(['resultado_prueba_id', 'criterio_id']);
            $table->foreign('resultado_prueba_id', 'fk_calificacion_resultado')->references('id')->on('talento.resultado_prueba')->cascadeOnDelete();
            $table->foreign('criterio_id', 'fk_calificacion_criterio')->references('id')->on('talento.rubrica_criterio');
            $table->index('criterio_id', 'ix_calificacion_criterio');
            $table->comment('Puntaje por criterio en revisiones manuales (CV, entrevista)');
        });

        Talento::finalizar('calificacion_criterio', [
            'ck_calificacion_puntaje' => 'puntaje >= 0',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.calificacion_criterio');
    }
};
