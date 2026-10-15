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
        Schema::create('talento.parametro_sistema', function (Blueprint $table) {
            $table->string('clave', 80)->primary();
            $table->string('valor', 500);
            $table->string('descripcion', 255)->nullable();
            $table->bigInteger('actualizado_por')->nullable();
            Talento::timestamps($table);

            $table->foreign('actualizado_por', 'fk_parametro_usuario')->references('id')->on('talento.usuario')->nullOnDelete();
            $table->index('actualizado_por', 'ix_parametro_usuario');
            $table->comment('Parámetros globales editables por el administrador');
        });

        Talento::finalizar('parametro_sistema');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.parametro_sistema');
    }
};
