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
        Schema::create('talento.solicitud_empresa', function (Blueprint $table) {
            $table->bigInteger('proceso_id')->primary();
            $table->string('tipo_proceso', 10)->default('EMPRESA');
            $table->bigInteger('contacto_id');
            $table->string('cargo', 150);
            $table->string('area', 100)->nullable();
            $table->string('nivel_jerarquico', 10);
            $table->smallInteger('vacantes')->default(1);
            $table->text('descripcion_funciones');
            $table->timestampTz('enviada_en')->nullable();
            Talento::timestamps($table);

            $table->foreign(['proceso_id', 'tipo_proceso'], 'fk_solicitud_proceso')->references(['id', 'tipo'])->on('talento.proceso');
            $table->foreign('contacto_id', 'fk_solicitud_contacto')->references('id')->on('talento.contacto_empresa');
            $table->index('contacto_id', 'ix_solicitud_contacto');
            $table->comment('Subtipo flujo B: la empresa se deduce del contacto solicitante (3FN)');
        });

        Talento::finalizar('solicitud_empresa', [
            'ck_solicitud_tipo' => "tipo_proceso = 'EMPRESA'",
            'ck_solicitud_nivel' => Talento::en('nivel_jerarquico', ['OPERATIVO', 'TECNICO', 'JEFATURA', 'GERENCIA', 'DIRECCION']),
            'ck_solicitud_vacantes' => 'vacantes >= 1',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.solicitud_empresa');
    }
};
