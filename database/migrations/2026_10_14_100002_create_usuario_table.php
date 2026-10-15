<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talento.usuario', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->integer('rol_id');
            $table->string('correo', 190);
            $table->string('password_hash', 255);
            $table->string('estado', 10)->default('PENDIENTE');
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('ultimo_acceso')->nullable();
            Talento::timestamps($table);

            $table->foreign('rol_id', 'fk_usuario_rol')->references('id')->on('talento.rol');
            $table->index('rol_id', 'ix_usuario_rol');
            $table->comment('Credenciales de acceso. Un único login; cada usuario tiene exactamente un rol');
        });

        DB::statement('CREATE UNIQUE INDEX uq_usuario_correo ON talento.usuario (lower(correo))');

        Talento::finalizar('usuario', [
            'ck_usuario_estado' => Talento::en('estado', ['PENDIENTE', 'ACTIVO', 'BLOQUEADO', 'INACTIVO']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.usuario');
    }
};
