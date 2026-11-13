<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('talento.prueba')
            ->where('codigo', 'TEST_LECTURA_COMPRENSIVA')
            ->update([
                'nombre' => 'Test de habilidades y aptitudes específicas',
                'descripcion' => 'Evaluación de habilidades y aptitudes profesionales específicas (análisis de textos técnicos, precisión y criterio profesional)',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('talento.prueba')
            ->where('codigo', 'TEST_LECTURA_COMPRENSIVA')
            ->update([
                'nombre' => 'Test de lectura comprensiva',
                'descripcion' => null,
            ]);
    }
};
