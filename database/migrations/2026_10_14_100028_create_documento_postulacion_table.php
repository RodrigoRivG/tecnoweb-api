<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talento.documento_postulacion', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->integer('tipo_documento_id');
            $table->bigInteger('archivo_id');
            $table->string('estado', 10)->default('PENDIENTE');
            $table->boolean('es_vigente')->default(true);
            $table->bigInteger('validado_por')->nullable();
            $table->timestampTz('validado_en')->nullable();
            $table->string('observacion', 500)->nullable();
            Talento::timestamps($table);

            $table->unique('archivo_id', 'uq_documento_archivo');
            $table->foreign('postulacion_id', 'fk_documento_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('tipo_documento_id', 'fk_documento_tipo')->references('id')->on('talento.tipo_documento');
            $table->foreign('archivo_id', 'fk_documento_archivo')->references('id')->on('talento.archivo');
            $table->foreign('validado_por', 'fk_documento_validado')->references('id')->on('talento.usuario');
            $table->index('postulacion_id', 'ix_documento_postulacion');
            $table->index('tipo_documento_id', 'ix_documento_tipo');
            $table->index('validado_por', 'ix_documento_validado');
            $table->comment('Documentos subidos por el postulante y su estado de validación');
        });

        // Un solo documento vigente por postulación y tipo; las versiones anteriores quedan como historial
        DB::statement('CREATE UNIQUE INDEX uq_documento_vigente ON talento.documento_postulacion (postulacion_id, tipo_documento_id) WHERE es_vigente');

        Talento::finalizar('documento_postulacion', [
            'ck_documento_postulacion_estado' => Talento::en('estado', ['PENDIENTE', 'VALIDADO', 'OBSERVADO', 'RECHAZADO']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.documento_postulacion');
    }
};
