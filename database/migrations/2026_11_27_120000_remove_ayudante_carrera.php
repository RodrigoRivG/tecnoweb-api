<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El ayudante es personal de la consultora (alcance global), no por carrera académica.
     */
    public function up(): void
    {
        Schema::dropIfExists('talento.ayudante_carrera');

        DB::table('talento.rol')->update(['alcance_carrera' => false]);
    }

    public function down(): void
    {
        // No se restaura la tabla: el modelo de negocio ya no usa alcance por carrera en usuarios.
    }
};