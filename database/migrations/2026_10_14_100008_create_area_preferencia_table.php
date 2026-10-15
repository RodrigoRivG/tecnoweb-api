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
        Schema::create('talento.area_preferencia', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->integer('carrera_id');
            $table->string('nombre', 100);
            Talento::timestamps($table);

            $table->unique(['carrera_id', 'nombre'], 'uq_area_carrera_nombre');
            $table->unique(['id', 'carrera_id'], 'uq_area_id_carrera');
            $table->foreign('carrera_id', 'fk_area_carrera')->references('id')->on('talento.carrera');
            $table->comment('Áreas de preferencia por carrera (ej. Derecho: penal, civil, laboral)');
        });

        Talento::finalizar('area_preferencia');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.area_preferencia');
    }
};
