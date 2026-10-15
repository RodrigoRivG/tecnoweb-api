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
        Schema::create('talento.ranking_resultado', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_id');
            $table->bigInteger('postulacion_id');
            $table->integer('posicion');
            $table->decimal('puntaje_total', 5, 2);
            $table->boolean('es_top')->default(false);
            $table->boolean('visible')->default(false);
            $table->timestampTz('publicado_en')->nullable();
            $table->bigInteger('publicado_por')->nullable();
            Talento::timestamps($table);

            $table->unique(['proceso_id', 'postulacion_id'], 'uq_ranking_postulacion');
            $table->unique(['proceso_id', 'posicion'], 'uq_ranking_posicion');
            $table->foreign('proceso_id', 'fk_ranking_proceso')->references('id')->on('talento.proceso');
            $table->foreign('postulacion_id', 'fk_ranking_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('publicado_por', 'fk_ranking_publicado_por')->references('id')->on('talento.usuario');
            $table->index('postulacion_id', 'ix_ranking_postulacion');
            $table->index(['visible', 'publicado_en'], 'ix_ranking_publico');
            $table->index('publicado_por', 'ix_ranking_publicado_por');
            $table->comment('Ranking congelado al cierre del proceso. es_top marca el top-N; visible controla la publicación en la landing');
        });

        Talento::finalizar('ranking_resultado', [
            'ck_ranking_puntaje' => 'puntaje_total BETWEEN 0 AND 100',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.ranking_resultado');
    }
};
