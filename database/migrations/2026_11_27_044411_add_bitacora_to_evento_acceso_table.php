<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talento.evento_acceso', function (Blueprint $table): void {
            $table->ipAddress('direccion_ip')->nullable();
        });

        DB::statement("CREATE INDEX ix_evento_acceso_login_fecha ON talento.evento_acceso (created_at DESC, id DESC) WHERE tipo IN ('LOGIN_EXITOSO', 'LOGIN_FALLIDO')");

        $permisoId = DB::table('talento.permiso')->insertGetId([
            'codigo' => 'bitacora.ver',
            'nombre' => 'Consultar bitácora de accesos',
            'modulo' => 'USUARIOS',
            'descripcion' => 'Consultar los resultados de inicio de sesión y las direcciones IP registradas.',
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
        DB::table('talento.permiso')->where('codigo', 'bitacora.ver')->delete();
        DB::statement('DROP INDEX talento.ix_evento_acceso_login_fecha');

        Schema::table('talento.evento_acceso', function (Blueprint $table): void {
            $table->dropColumn('direccion_ip');
        });
    }
};
