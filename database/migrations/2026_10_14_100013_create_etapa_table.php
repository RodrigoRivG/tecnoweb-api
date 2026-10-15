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
        Schema::create('talento.etapa', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 40);
            $table->string('nombre', 100);
            $table->string('ejecucion', 10);
            $table->string('descripcion', 255)->nullable();
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_etapa_codigo');
            $table->comment('Catálogo de tipos de etapa. MANUAL genera una tarea en la bandeja del ayudante');
        });

        Talento::finalizar('etapa', [
            'ck_etapa_ejecucion' => Talento::en('ejecucion', ['AUTOMATICA', 'MANUAL']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.etapa');
    }
};
