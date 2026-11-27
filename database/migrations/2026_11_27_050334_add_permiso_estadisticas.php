<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permisoId = DB::table('talento.permiso')->insertGetId([
            'codigo' => 'estadisticas.ver',
            'nombre' => 'Consultar estadísticas',
            'modulo' => 'USUARIOS',
            'descripcion' => 'Consultar las métricas de visitas, accesos y postulaciones por etapa.',
        ]);

        $administradorId = DB::table('talento.rol')->where('codigo', 'ADMIN')->value('id');

        if ($administradorId !== null) {
            DB::table('talento.rol_permiso')->insert([
                'rol_id' => $administradorId,
                'permiso_id' => $permisoId,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('talento.permiso')->where('codigo', 'estadisticas.ver')->delete();
    }
};
