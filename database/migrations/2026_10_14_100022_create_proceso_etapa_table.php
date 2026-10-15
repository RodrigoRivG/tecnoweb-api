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
        Schema::create('talento.proceso_etapa', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_id');
            $table->integer('etapa_id');
            $table->smallInteger('orden');
            $table->boolean('es_eliminatoria')->default(true);
            $table->boolean('activa')->default(true);
            Talento::timestamps($table);

            $table->unique(['proceso_id', 'etapa_id'], 'uq_proceso_etapa_etapa');
            $table->unique(['proceso_id', 'orden'], 'uq_proceso_etapa_orden');
            $table->foreign('proceso_id', 'fk_proceso_etapa_proceso')->references('id')->on('talento.proceso');
            $table->foreign('etapa_id', 'fk_proceso_etapa_etapa')->references('id')->on('talento.etapa');
            $table->index('etapa_id', 'ix_proceso_etapa_etapa');
            $table->comment('Etapas que componen un proceso, en orden, y si eliminan al candidato que no las supera');
        });

        Talento::finalizar('proceso_etapa');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.proceso_etapa');
    }
};
