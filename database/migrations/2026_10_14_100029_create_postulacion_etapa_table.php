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
        Schema::create('talento.postulacion_etapa', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->bigInteger('proceso_etapa_id');
            $table->string('estado', 11)->default('BLOQUEADA');
            $table->bigInteger('asignado_a')->nullable();
            $table->timestampTz('asignado_en')->nullable();
            $table->timestampTz('iniciada_en')->nullable();
            $table->timestampTz('finalizada_en')->nullable();
            $table->string('motivo', 500)->nullable();
            Talento::timestamps($table);

            $table->unique(['postulacion_id', 'proceso_etapa_id'], 'uq_postulacion_etapa');
            $table->foreign('postulacion_id', 'fk_postulacion_etapa_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('proceso_etapa_id', 'fk_postulacion_etapa_proceso')->references('id')->on('talento.proceso_etapa');
            $table->foreign('asignado_a', 'fk_postulacion_etapa_asignado')->references('id')->on('talento.usuario');
            $table->index(['asignado_a', 'estado'], 'ix_postulacion_etapa_bandeja');
            $table->index(['proceso_etapa_id', 'estado'], 'ix_postulacion_etapa_embudo');
            $table->comment('Máquina de estados: avance del candidato por cada etapa. Las filas MANUAL en revisión forman la bandeja de tareas');
        });

        Talento::finalizar('postulacion_etapa', [
            'ck_postulacion_etapa_estado' => Talento::en('estado', ['BLOQUEADA', 'PENDIENTE', 'EN_CURSO', 'EN_REVISION', 'APROBADA', 'REPROBADA', 'OMITIDA']),
            'ck_postulacion_etapa_fechas' => 'finalizada_en IS NULL OR iniciada_en IS NULL OR finalizada_en >= iniciada_en',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.postulacion_etapa');
    }
};
