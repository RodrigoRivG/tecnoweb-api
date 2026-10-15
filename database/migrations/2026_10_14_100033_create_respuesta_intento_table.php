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
        Schema::create('talento.respuesta_intento', function (Blueprint $table) {
            $table->bigInteger('intento_id');
            $table->bigInteger('pregunta_id');
            $table->bigInteger('opcion_id')->nullable();
            $table->timestampTz('respondida_en')->useCurrent();
            Talento::timestamps($table);

            $table->primary(['intento_id', 'pregunta_id']);
            $table->foreign('intento_id', 'fk_respuesta_intento')->references('id')->on('talento.intento_prueba')->cascadeOnDelete();
            $table->foreign('pregunta_id', 'fk_respuesta_pregunta')->references('id')->on('talento.pregunta');
            // La opción elegida debe pertenecer a la pregunta respondida (se omite si opcion_id es NULL = sin responder)
            $table->foreign(['opcion_id', 'pregunta_id'], 'fk_respuesta_opcion')->references(['id', 'pregunta_id'])->on('talento.opcion_pregunta');
            $table->index('pregunta_id', 'ix_respuesta_pregunta');
            $table->index(['opcion_id', 'pregunta_id'], 'ix_respuesta_opcion');
            $table->comment('Respuestas de un intento. El puntaje se calcula uniendo con opcion_pregunta (no se duplica)');
        });

        Talento::finalizar('respuesta_intento');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.respuesta_intento');
    }
};
