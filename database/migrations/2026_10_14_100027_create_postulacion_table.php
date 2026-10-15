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
        Schema::create('talento.postulacion', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('codigo', 20);
            $table->bigInteger('proceso_id');
            $table->bigInteger('persona_id');
            $table->bigInteger('registrada_por')->nullable();
            $table->string('estado', 11)->default('INSCRITA');
            $table->string('motivo_cierre', 500)->nullable();
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_postulacion_codigo');
            $table->unique(['proceso_id', 'persona_id'], 'uq_postulacion_proceso_persona');
            $table->foreign('proceso_id', 'fk_postulacion_proceso')->references('id')->on('talento.proceso');
            $table->foreign('persona_id', 'fk_postulacion_persona')->references('id')->on('talento.persona');
            $table->foreign('registrada_por', 'fk_postulacion_registrada_por')->references('id')->on('talento.contacto_empresa');
            $table->index('persona_id', 'ix_postulacion_persona');
            $table->index(['proceso_id', 'estado'], 'ix_postulacion_proceso_estado');
            $table->index('registrada_por', 'ix_postulacion_registrada_por');
            $table->comment('Candidato dentro de un proceso. codigo es el identificador seudónimo que se muestra en la landing pública');
        });

        Talento::finalizar('postulacion', [
            'ck_postulacion_estado' => Talento::en('estado', ['INSCRITA', 'EN_PROCESO', 'APROBADA', 'NO_APROBADA', 'RETIRADA']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.postulacion');
    }
};
