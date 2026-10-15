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
        Schema::create('talento.egresado', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('persona_id');
            $table->integer('carrera_id');
            $table->integer('area_preferencia_id')->nullable();
            $table->string('registro_universitario', 20);
            $table->smallInteger('anio_egreso');
            $table->decimal('ppa', 5, 2);
            $table->boolean('titulo_verificado')->default(false);
            Talento::timestamps($table);

            $table->unique('registro_universitario', 'uq_egresado_registro');
            $table->unique(['persona_id', 'carrera_id'], 'uq_egresado_persona_carrera');
            $table->foreign('persona_id', 'fk_egresado_persona')->references('id')->on('talento.persona');
            $table->foreign('carrera_id', 'fk_egresado_carrera')->references('id')->on('talento.carrera');
            $table->foreign(['area_preferencia_id', 'carrera_id'], 'fk_egresado_area')
                ->references(['id', 'carrera_id'])->on('talento.area_preferencia');
            $table->index('carrera_id', 'ix_egresado_carrera');
            $table->comment('Perfil académico por carrera de un egresado titulado. La facultad se deduce de la carrera');
        });

        Talento::finalizar('egresado', [
            'ck_egresado_ppa' => 'ppa BETWEEN 0 AND 100',
            'ck_egresado_anio' => 'anio_egreso BETWEEN 1950 AND 2100',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.egresado');
    }
};
