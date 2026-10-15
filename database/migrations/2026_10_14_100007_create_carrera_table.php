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
        Schema::create('talento.carrera', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->integer('facultad_id');
            $table->string('nombre', 150);
            $table->boolean('activa')->default(true);
            Talento::timestamps($table);

            $table->unique(['facultad_id', 'nombre'], 'uq_carrera_facultad_nombre');
            $table->foreign('facultad_id', 'fk_carrera_facultad')->references('id')->on('talento.facultad');
            $table->comment('Carreras de cada facultad');
        });

        Talento::finalizar('carrera');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.carrera');
    }
};
