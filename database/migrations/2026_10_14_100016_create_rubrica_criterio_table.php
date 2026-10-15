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
        Schema::create('talento.rubrica_criterio', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->integer('prueba_id');
            $table->string('tipo_prueba', 10)->default('RUBRICA');
            $table->string('nombre', 100);
            $table->decimal('puntaje_maximo', 5, 2);
            $table->smallInteger('orden')->default(1);
            Talento::timestamps($table);

            $table->unique(['prueba_id', 'nombre'], 'uq_criterio_prueba_nombre');
            // Discriminador: solo una prueba de tipo RUBRICA puede tener criterios
            $table->foreign(['prueba_id', 'tipo_prueba'], 'fk_criterio_prueba')->references(['id', 'tipo'])->on('talento.prueba');
            $table->comment('Criterios de una prueba tipo RUBRICA (ej. CV: formación, experiencia, habilidades, presentación)');
        });

        Talento::finalizar('rubrica_criterio', [
            'ck_criterio_tipo' => "tipo_prueba = 'RUBRICA'",
            'ck_criterio_maximo' => 'puntaje_maximo > 0',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.rubrica_criterio');
    }
};
