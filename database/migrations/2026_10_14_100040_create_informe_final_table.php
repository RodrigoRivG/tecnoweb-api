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
        Schema::create('talento.informe_final', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_id');
            $table->string('estado', 10)->default('BORRADOR');
            $table->text('resumen')->nullable();
            $table->text('recomendacion_general')->nullable();
            $table->bigInteger('archivo_pdf_id')->nullable();
            $table->bigInteger('elaborado_por');
            $table->timestampTz('emitido_en')->nullable();
            Talento::timestamps($table);

            $table->unique('proceso_id', 'uq_informe_proceso');
            $table->foreign('proceso_id', 'fk_informe_proceso')->references('id')->on('talento.proceso');
            $table->foreign('archivo_pdf_id', 'fk_informe_pdf')->references('id')->on('talento.archivo');
            $table->foreign('elaborado_por', 'fk_informe_elaborado')->references('id')->on('talento.usuario');
            $table->index('elaborado_por', 'ix_informe_elaborado_por');
            $table->index('archivo_pdf_id', 'ix_informe_pdf');
            $table->comment('Informe y recomendaciones que recibe la empresa al terminar su evaluación');
        });

        Talento::finalizar('informe_final', [
            'ck_informe_estado' => Talento::en('estado', ['BORRADOR', 'EMITIDO']),
            'ck_informe_emision' => "estado = 'BORRADOR' OR emitido_en IS NOT NULL",
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.informe_final');
    }
};
