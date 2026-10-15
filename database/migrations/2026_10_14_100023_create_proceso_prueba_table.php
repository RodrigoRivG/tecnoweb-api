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
        Schema::create('talento.proceso_prueba', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_etapa_id');
            $table->integer('prueba_id');
            $table->smallInteger('orden')->default(1);
            $table->decimal('peso', 5, 2)->default(0);
            $table->boolean('obligatoria')->default(true);
            $table->decimal('puntaje_minimo', 5, 2)->nullable();
            $table->string('umbral_origen', 10)->default('FIJO');
            $table->integer('tiempo_minimo_seg')->nullable();
            $table->integer('tiempo_maximo_seg')->nullable();
            $table->smallInteger('max_intentos')->default(1);
            Talento::timestamps($table);

            $table->unique(['proceso_etapa_id', 'prueba_id'], 'uq_proceso_prueba');
            $table->foreign('proceso_etapa_id', 'fk_proceso_prueba_etapa')->references('id')->on('talento.proceso_etapa');
            $table->foreign('prueba_id', 'fk_proceso_prueba_prueba')->references('id')->on('talento.prueba');
            $table->index('prueba_id', 'ix_proceso_prueba_prueba');
            $table->comment('Configuración de cada prueba dentro de un proceso: peso, nota mínima, tiempos e intentos. umbral_origen=FACULTAD usa facultad.ppa_minimo');
        });

        Talento::finalizar('proceso_prueba', [
            'ck_proceso_prueba_umbral' => Talento::en('umbral_origen', ['FIJO', 'FACULTAD']),
            'ck_proceso_prueba_peso' => 'peso BETWEEN 0 AND 100',
            'ck_proceso_prueba_minimo' => 'puntaje_minimo IS NULL OR puntaje_minimo BETWEEN 0 AND 100',
            'ck_proceso_prueba_intentos' => 'max_intentos >= 1',
            'ck_proceso_prueba_tiempos' => 'tiempo_maximo_seg IS NULL OR tiempo_minimo_seg IS NULL OR tiempo_maximo_seg >= tiempo_minimo_seg',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.proceso_prueba');
    }
};
