<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El ayudante ya no se limita por carrera. La bandeja no usa esta tabla.
     */
    public function up(): void
    {
        Schema::dropIfExists('talento.ayudante_carrera');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La tabla se retiró a propósito. No se vuelve a crear.
    }
};
