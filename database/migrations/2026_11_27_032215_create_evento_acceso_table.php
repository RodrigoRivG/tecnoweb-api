<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talento.evento_acceso', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('usuario_id')->nullable();
            $table->string('tipo', 24);
            $table->string('ruta', 120)->nullable();
            $table->uuid('clave_visita')->nullable();
            Talento::timestamps($table);

            $table->foreign('usuario_id', 'fk_evento_acceso_usuario')->references('id')->on('talento.usuario')->nullOnDelete();
            $table->index('usuario_id', 'ix_evento_acceso_usuario');
            $table->index(['tipo', 'ruta', 'created_at'], 'ix_evento_acceso_tipo_ruta_fecha');
            $table->unique('clave_visita', 'uq_evento_acceso_clave_visita');
            $table->comment('Eventos de acceso al sitio, con usuario opcional y ruta de la vista visitada.');
        });

        Talento::finalizar('evento_acceso', [
            'ck_evento_acceso_tipo' => Talento::en('tipo', ['VISITA', 'LOGIN_EXITOSO', 'LOGIN_FALLIDO']),
            'ck_evento_acceso_visita' => "tipo <> 'VISITA' OR (ruta IS NOT NULL AND clave_visita IS NOT NULL)",
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('talento.evento_acceso');
    }
};
