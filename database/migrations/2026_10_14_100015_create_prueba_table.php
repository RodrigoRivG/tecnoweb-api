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
        Schema::create('talento.prueba', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 50);
            $table->string('nombre', 120);
            $table->string('tipo', 10);
            $table->text('descripcion')->nullable();
            $table->boolean('es_plantilla')->default(true);
            $table->boolean('activa')->default(true);
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_prueba_codigo');
            $table->unique(['id', 'tipo'], 'uq_prueba_id_tipo');
            $table->comment('Instrumentos puntuables reutilizables: TEST en línea, RUBRICA manual (CV, entrevista) o CALCULADA (PPA)');
        });

        Talento::finalizar('prueba', [
            'ck_prueba_tipo' => Talento::en('tipo', ['TEST', 'RUBRICA', 'CALCULADA']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.prueba');
    }
};
