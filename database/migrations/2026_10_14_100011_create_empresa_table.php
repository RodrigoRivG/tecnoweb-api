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
        Schema::create('talento.empresa', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('razon_social', 200);
            $table->string('nit', 20);
            $table->string('correo', 190);
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('estado', 10)->default('ACTIVA');
            Talento::timestamps($table);

            $table->unique('nit', 'uq_empresa_nit');
            $table->comment('Empresas que contratan evaluaciones o consultan talento');
        });

        Talento::finalizar('empresa', [
            'ck_empresa_estado' => Talento::en('estado', ['ACTIVA', 'INACTIVA']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.empresa');
    }
};
