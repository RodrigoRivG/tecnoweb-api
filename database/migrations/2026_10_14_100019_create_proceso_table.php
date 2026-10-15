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
        Schema::create('talento.proceso', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('codigo', 30);
            $table->string('tipo', 10);
            $table->string('nombre', 200);
            $table->string('estado', 15)->default('BORRADOR');
            $table->smallInteger('top_n')->default(5);
            $table->bigInteger('creado_por');
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_proceso_codigo');
            $table->unique(['id', 'tipo'], 'uq_proceso_id_tipo');
            $table->foreign('creado_por', 'fk_proceso_creado_por')->references('id')->on('talento.usuario');
            $table->index(['tipo', 'estado'], 'ix_proceso_tipo_estado');
            $table->index('creado_por', 'ix_proceso_creado_por');
            $table->comment('Supertipo: motor común de evaluación para convocatorias de talento y solicitudes de empresa');
        });

        Talento::finalizar('proceso', [
            'ck_proceso_tipo' => Talento::en('tipo', ['TALENTO', 'EMPRESA']),
            'ck_proceso_estado' => Talento::en('estado', ['BORRADOR', 'CONFIGURACION', 'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'INFORME_EMITIDO', 'CANCELADO']),
            'ck_proceso_top' => 'top_n BETWEEN 1 AND 50',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.proceso');
    }
};
