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
        Schema::create('talento.intento_prueba', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->bigInteger('proceso_prueba_id');
            $table->smallInteger('nro_intento')->default(1);
            $table->string('estado', 10)->default('EN_CURSO');
            $table->timestampTz('iniciado_en')->useCurrent();
            $table->timestampTz('finalizado_en')->nullable();
            $table->integer('duracion_seg')->nullable()->storedAs('EXTRACT(EPOCH FROM (finalizado_en - iniciado_en))::integer');
            Talento::timestamps($table);

            $table->unique(['postulacion_id', 'proceso_prueba_id', 'nro_intento'], 'uq_intento');
            $table->unique(['id', 'postulacion_id', 'proceso_prueba_id'], 'uq_intento_id_post_prueba');
            $table->foreign('postulacion_id', 'fk_intento_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('proceso_prueba_id', 'fk_intento_proceso_prueba')->references('id')->on('talento.proceso_prueba');
            $table->index('proceso_prueba_id', 'ix_intento_proceso_prueba');
            $table->comment('Cada vez que un candidato rinde un test en línea; duracion_seg permite aplicar tiempo mínimo y máximo');
        });

        Talento::finalizar('intento_prueba', [
            'ck_intento_estado' => Talento::en('estado', ['EN_CURSO', 'FINALIZADO', 'EXPIRADO', 'ANULADO']),
            'ck_intento_fechas' => 'finalizado_en IS NULL OR finalizado_en >= iniciado_en',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.intento_prueba');
    }
};
