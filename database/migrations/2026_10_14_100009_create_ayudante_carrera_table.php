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
        Schema::create('talento.ayudante_carrera', function (Blueprint $table) {
            $table->bigInteger('usuario_id');
            $table->integer('carrera_id');
            Talento::timestamps($table);

            $table->primary(['usuario_id', 'carrera_id']);
            $table->foreign('usuario_id', 'fk_ayudante_usuario')->references('id')->on('talento.usuario')->cascadeOnDelete();
            $table->foreign('carrera_id', 'fk_ayudante_carrera')->references('id')->on('talento.carrera');
            $table->index('carrera_id', 'ix_ayudante_carrera_carrera');
            $table->comment('Carreras que atiende cada junior/ayudante; define su bandeja de tareas manuales');
        });

        Talento::finalizar('ayudante_carrera');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.ayudante_carrera');
    }
};
