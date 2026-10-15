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
        Schema::create('talento.opcion_pregunta', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('pregunta_id');
            $table->char('etiqueta', 1);
            $table->string('texto', 500)->nullable();
            $table->bigInteger('imagen_archivo_id')->nullable();
            $table->decimal('puntaje', 5, 2)->default(0);
            Talento::timestamps($table);

            $table->unique(['pregunta_id', 'etiqueta'], 'uq_opcion_pregunta_etiqueta');
            $table->unique(['id', 'pregunta_id'], 'uq_opcion_id_pregunta');
            $table->foreign('pregunta_id', 'fk_opcion_pregunta')->references('id')->on('talento.pregunta')->cascadeOnDelete();
            $table->foreign('imagen_archivo_id', 'fk_opcion_imagen')->references('id')->on('talento.archivo');
            $table->index('imagen_archivo_id', 'ix_opcion_imagen');
            $table->comment('Opciones A–E. Cada opción aporta un puntaje: sirve para pruebas con respuesta correcta y para personalidad');
        });

        Talento::finalizar('opcion_pregunta', [
            'ck_opcion_puntaje' => 'puntaje >= 0',
            'ck_opcion_contenido' => 'texto IS NOT NULL OR imagen_archivo_id IS NOT NULL',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.opcion_pregunta');
    }
};
