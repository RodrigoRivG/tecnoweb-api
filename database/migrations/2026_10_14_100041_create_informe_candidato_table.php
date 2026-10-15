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
        Schema::create('talento.informe_candidato', function (Blueprint $table) {
            $table->bigInteger('informe_id');
            $table->bigInteger('postulacion_id');
            $table->string('etiqueta', 24);
            $table->text('recomendacion')->nullable();
            Talento::timestamps($table);

            $table->primary(['informe_id', 'postulacion_id']);
            $table->foreign('informe_id', 'fk_informe_candidato_informe')->references('id')->on('talento.informe_final')->cascadeOnDelete();
            $table->foreign('postulacion_id', 'fk_informe_candidato_postulacion')->references('id')->on('talento.postulacion');
            $table->index('postulacion_id', 'ix_informe_candidato_postulacion');
            $table->comment('Dictamen de la consultora por candidato dentro del informe');
        });

        Talento::finalizar('informe_candidato', [
            'ck_informe_candidato_etiqueta' => Talento::en('etiqueta', ['ALTAMENTE_RECOMENDADO', 'RECOMENDADO', 'RECOMENDADO_CON_RESERVAS', 'NO_RECOMENDADO']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.informe_candidato');
    }
};
