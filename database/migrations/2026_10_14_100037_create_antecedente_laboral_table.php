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
        Schema::create('talento.antecedente_laboral', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->string('empresa_nombre', 200);
            $table->string('cargo', 150);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('referente_nombre', 150)->nullable();
            $table->string('referente_telefono', 30)->nullable();
            $table->string('estado_verificacion', 13)->default('NO_CONTACTADO');
            $table->date('fecha_contacto')->nullable();
            $table->string('persona_contactada', 150)->nullable();
            $table->smallInteger('calificacion_desempeno')->nullable();
            $table->boolean('es_puntual')->nullable();
            $table->boolean('es_responsable')->nullable();
            $table->boolean('trabaja_en_equipo')->nullable();
            $table->string('motivo_salida', 300)->nullable();
            $table->boolean('lo_recontrataria')->nullable();
            $table->text('observaciones')->nullable();
            $table->bigInteger('verificado_por')->nullable();
            Talento::timestamps($table);

            $table->foreign('postulacion_id', 'fk_antecedente_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('verificado_por', 'fk_antecedente_verificador')->references('id')->on('talento.usuario');
            $table->index('postulacion_id', 'ix_antecedente_postulacion');
            $table->index('verificado_por', 'ix_antecedente_verificador');
            $table->comment('Cada empleo declarado por el candidato y el resultado de verificarlo con la referencia');
        });

        Talento::finalizar('antecedente_laboral', [
            'ck_antecedente_estado' => Talento::en('estado_verificacion', ['NO_CONTACTADO', 'CONTACTADO', 'VERIFICADO', 'INCONSISTENTE']),
            'ck_antecedente_fechas' => 'fecha_fin IS NULL OR fecha_fin >= fecha_inicio',
            'ck_antecedente_calificacion' => 'calificacion_desempeno IS NULL OR calificacion_desempeno BETWEEN 1 AND 5',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.antecedente_laboral');
    }
};
