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
        $estados = ['BLOQUEADA', 'PENDIENTE', 'EN_CURSO', 'EN_REVISION', 'APROBADA', 'REPROBADA', 'OMITIDA'];

        Schema::create('talento.historial_postulacion_etapa', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_etapa_id');
            $table->string('estado_anterior', 11)->nullable();
            $table->string('estado_nuevo', 11);
            $table->bigInteger('usuario_id')->nullable();
            $table->string('motivo', 500)->nullable();
            Talento::timestamps($table, 3);

            $table->foreign('postulacion_etapa_id', 'fk_historial_postulacion_etapa')->references('id')->on('talento.postulacion_etapa');
            $table->foreign('usuario_id', 'fk_historial_usuario')->references('id')->on('talento.usuario')->nullOnDelete();
            $table->index(['postulacion_etapa_id', 'created_at'], 'ix_historial_etapa');
            $table->index('usuario_id', 'ix_historial_usuario');
            $table->comment('Quién cambió el estado de cada etapa y cuándo (lo alimentan los triggers de postulacion_etapa)');
        });

        Talento::finalizar('historial_postulacion_etapa', [
            'ck_historial_estado_anterior' => Talento::en('estado_anterior', $estados),
            'ck_historial_estado_nuevo' => Talento::en('estado_nuevo', $estados),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.historial_postulacion_etapa');
    }
};
