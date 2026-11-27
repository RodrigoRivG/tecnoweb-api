<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $purdueId = DB::table('talento.prueba')->where('codigo', 'TEST_PURDUE_NO_VERBAL')->value('id');

        if ($purdueId === null) {
            return;
        }

        DB::table('talento.prueba')->where('id', $purdueId)->update([
            'familia' => 'INTELIGENCIA',
            'descripcion' => 'Test no verbal Forma A. Razonamiento abstracto. 48 series.',
        ]);

        DB::table('talento.prueba_carrera')->where('prueba_id', $purdueId)->delete();

        DB::table('talento.proceso_prueba')
            ->where('prueba_id', $purdueId)
            ->update(['modo_asignacion' => 'ALTERNA']);
    }

    public function down(): void
    {
        $purdueId = DB::table('talento.prueba')->where('codigo', 'TEST_PURDUE_NO_VERBAL')->value('id');

        if ($purdueId === null) {
            return;
        }

        DB::table('talento.prueba')->where('id', $purdueId)->update([
            'familia' => 'HABILIDAD_ESPECIFICA',
            'descripcion' => 'Aptitud espacial. 48 series.',
        ]);

        DB::table('talento.proceso_prueba')
            ->where('prueba_id', $purdueId)
            ->update(['modo_asignacion' => 'FIJA']);
    }
};