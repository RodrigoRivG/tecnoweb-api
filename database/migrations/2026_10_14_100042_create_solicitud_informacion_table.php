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
        Schema::create('talento.solicitud_informacion', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('codigo_seguimiento', 20);
            $table->bigInteger('ranking_resultado_id');
            $table->string('empresa_nombre', 200);
            $table->string('nit', 20);
            $table->string('contacto_nombre', 150);
            $table->string('contacto_cargo', 100);
            $table->string('contacto_correo', 190);
            $table->string('contacto_telefono', 30)->nullable();
            $table->string('vacante', 200);
            $table->text('mensaje')->nullable();
            $table->boolean('acepta_politica');
            $table->string('estado', 10)->default('PENDIENTE');
            $table->string('motivo_rechazo', 500)->nullable();
            $table->bigInteger('resuelto_por')->nullable();
            $table->timestampTz('resuelto_en')->nullable();
            $table->timestampTz('entregada_en')->nullable();
            Talento::timestamps($table);

            $table->unique('codigo_seguimiento', 'uq_solicitud_info_codigo');
            $table->foreign('ranking_resultado_id', 'fk_solicitud_info_ranking')->references('id')->on('talento.ranking_resultado');
            $table->foreign('resuelto_por', 'fk_solicitud_info_resuelto')->references('id')->on('talento.usuario');
            $table->index(['estado', 'created_at'], 'ix_solicitud_info_estado');
            $table->index('ranking_resultado_id', 'ix_solicitud_info_ranking');
            $table->index('resuelto_por', 'ix_solicitud_info_resuelto');
            $table->comment('Pedido público de una empresa para obtener datos de contacto de un perfil. La consultora aprueba y entrega');
        });

        Talento::finalizar('solicitud_informacion', [
            'ck_solicitud_info_estado' => Talento::en('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'ENTREGADA']),
            'ck_solicitud_info_politica' => 'acepta_politica',
            'ck_solicitud_info_rechazo' => "estado <> 'RECHAZADA' OR motivo_rechazo IS NOT NULL",
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.solicitud_informacion');
    }
};
