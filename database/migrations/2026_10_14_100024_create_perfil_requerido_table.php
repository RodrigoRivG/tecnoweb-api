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
        Schema::create('talento.perfil_requerido', function (Blueprint $table) {
            $table->bigInteger('proceso_id');
            $table->integer('dimension_id');
            $table->smallInteger('valor_ideal');
            Talento::timestamps($table);

            $table->primary(['proceso_id', 'dimension_id']);
            $table->foreign('proceso_id', 'fk_perfil_proceso')->references('id')->on('talento.proceso')->cascadeOnDelete();
            $table->foreign('dimension_id', 'fk_perfil_dimension')->references('id')->on('talento.dimension_evaluada');
            $table->index('dimension_id', 'ix_perfil_dimension');
            $table->comment('Perfil psicológico y de habilidades ideal de un proceso (valor 1–10 por dimensión)');
        });

        Talento::finalizar('perfil_requerido', [
            'ck_perfil_valor' => 'valor_ideal BETWEEN 1 AND 10',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.perfil_requerido');
    }
};
