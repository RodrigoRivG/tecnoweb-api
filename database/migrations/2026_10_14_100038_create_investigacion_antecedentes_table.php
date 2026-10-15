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
        Schema::create('talento.investigacion_antecedentes', function (Blueprint $table) {
            $table->bigInteger('postulacion_id')->primary();
            $table->string('semaforo', 17);
            $table->text('conclusion')->nullable();
            $table->bigInteger('investigador_id')->nullable();
            $table->timestampTz('completada_en')->nullable();
            Talento::timestamps($table);

            $table->foreign('postulacion_id', 'fk_investigacion_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('investigador_id', 'fk_investigacion_investigador')->references('id')->on('talento.usuario');
            $table->index('investigador_id', 'ix_investigacion_investigador');
            $table->comment('Conclusión global de la investigación de antecedentes de un candidato (semáforo)');
        });

        Talento::finalizar('investigacion_antecedentes', [
            'ck_investigacion_semaforo' => Talento::en('semaforo', ['SIN_OBSERVACIONES', 'CON_OBSERVACIONES', 'ALERTA']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.investigacion_antecedentes');
    }
};
