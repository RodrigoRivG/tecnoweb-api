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
        Schema::create('talento.rol', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 30);
            $table->string('nombre', 60);
            $table->string('descripcion', 255)->nullable();
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_rol_codigo');
            $table->comment('Roles del sistema: administrador, ayudante, egresado, candidato externo, empresa. Un usuario tiene un solo rol');
        });

        Talento::finalizar('rol');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.rol');
    }
};
