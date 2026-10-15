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
        Schema::create('talento.resultado_dimension', function (Blueprint $table) {
            $table->bigInteger('resultado_prueba_id');
            $table->integer('dimension_id');
            $table->decimal('valor', 4, 2);
            Talento::timestamps($table);

            $table->primary(['resultado_prueba_id', 'dimension_id']);
            $table->foreign('resultado_prueba_id', 'fk_resultado_dimension_resultado')->references('id')->on('talento.resultado_prueba')->cascadeOnDelete();
            $table->foreign('dimension_id', 'fk_resultado_dimension_dimension')->references('id')->on('talento.dimension_evaluada');
            $table->index('dimension_id', 'ix_resultado_dimension');
            $table->comment('Valor real del candidato por dimensión (0–10); se compara con perfil_requerido para graficar ideal vs real');
        });

        Talento::finalizar('resultado_dimension', [
            'ck_resultado_dimension_valor' => 'valor BETWEEN 0 AND 10',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.resultado_dimension');
    }
};
