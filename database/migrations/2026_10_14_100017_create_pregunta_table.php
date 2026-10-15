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
        Schema::create('talento.pregunta', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->integer('prueba_id');
            $table->string('tipo_prueba', 10)->default('TEST');
            $table->integer('dimension_id')->nullable();
            $table->smallInteger('orden');
            $table->text('enunciado')->nullable();
            $table->bigInteger('imagen_archivo_id')->nullable();
            Talento::timestamps($table);

            $table->unique(['prueba_id', 'orden'], 'uq_pregunta_prueba_orden');
            // Discriminador: solo una prueba de tipo TEST puede tener preguntas
            $table->foreign(['prueba_id', 'tipo_prueba'], 'fk_pregunta_prueba')->references(['id', 'tipo'])->on('talento.prueba');
            $table->foreign('dimension_id', 'fk_pregunta_dimension')->references('id')->on('talento.dimension_evaluada');
            $table->foreign('imagen_archivo_id', 'fk_pregunta_imagen')->references('id')->on('talento.archivo');
            $table->index('dimension_id', 'ix_pregunta_dimension');
            $table->index('imagen_archivo_id', 'ix_pregunta_imagen');
            $table->comment('Banco de preguntas de un TEST (texto y/o imagen de la figura)');
        });

        Talento::finalizar('pregunta', [
            'ck_pregunta_tipo' => "tipo_prueba = 'TEST'",
            'ck_pregunta_contenido' => 'enunciado IS NOT NULL OR imagen_archivo_id IS NOT NULL',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.pregunta');
    }
};
