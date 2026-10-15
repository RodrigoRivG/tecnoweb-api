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
        Schema::create('talento.facultad', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('nombre', 150);
            $table->string('sigla', 15)->nullable();
            $table->decimal('ppa_minimo', 5, 2)->default(51.00);
            $table->boolean('activa')->default(true);
            Talento::timestamps($table);

            $table->unique('nombre', 'uq_facultad_nombre');
            $table->unique('sigla', 'uq_facultad_sigla');
            $table->comment('Facultades UAGRM. ppa_minimo lo define la administración y rige el filtro de postulación del flujo A');
        });

        Talento::finalizar('facultad', [
            'ck_facultad_ppa' => 'ppa_minimo BETWEEN 0 AND 100',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.facultad');
    }
};
