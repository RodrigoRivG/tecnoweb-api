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
        Schema::create('talento.dimension_evaluada', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 40);
            $table->string('nombre', 80);
            $table->string('tipo', 16);
            $table->smallInteger('orden')->default(0);
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_dimension_codigo');
            $table->comment('Factores de personalidad y habilidades medibles; base del gráfico perfil ideal vs real');
        });

        Talento::finalizar('dimension_evaluada', [
            'ck_dimension_evaluada_tipo' => Talento::en('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA', 'HABILIDAD_DURA']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.dimension_evaluada');
    }
};
