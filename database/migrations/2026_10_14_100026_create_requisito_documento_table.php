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
        Schema::create('talento.requisito_documento', function (Blueprint $table) {
            $table->bigInteger('proceso_id');
            $table->integer('tipo_documento_id');
            $table->boolean('obligatorio')->default(true);
            Talento::timestamps($table);

            $table->primary(['proceso_id', 'tipo_documento_id']);
            $table->foreign('proceso_id', 'fk_requisito_proceso')->references('id')->on('talento.proceso')->cascadeOnDelete();
            $table->foreign('tipo_documento_id', 'fk_requisito_tipo')->references('id')->on('talento.tipo_documento');
            $table->index('tipo_documento_id', 'ix_requisito_tipo');
            $table->comment('Documentos que cada proceso exige a sus postulantes');
        });

        Talento::finalizar('requisito_documento');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.requisito_documento');
    }
};
