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
        Schema::create('talento.convocatoria_talento', function (Blueprint $table) {
            $table->bigInteger('proceso_id')->primary();
            $table->string('tipo_proceso', 10)->default('TALENTO');
            $table->integer('carrera_id');
            $table->smallInteger('gestion');
            $table->smallInteger('numero')->default(1);
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->smallInteger('vigencia_perfil_meses')->default(12);
            Talento::timestamps($table);

            $table->unique(['carrera_id', 'gestion', 'numero'], 'uq_convocatoria_carrera_gestion');
            $table->foreign(['proceso_id', 'tipo_proceso'], 'fk_convocatoria_proceso')->references(['id', 'tipo'])->on('talento.proceso');
            $table->foreign('carrera_id', 'fk_convocatoria_carrera')->references('id')->on('talento.carrera');
            $table->comment('Subtipo flujo A: convocatoria por carrera y gestión. Su ranking alimenta la landing pública');
        });

        Talento::finalizar('convocatoria_talento', [
            'ck_convocatoria_tipo' => "tipo_proceso = 'TALENTO'",
            'ck_convocatoria_fechas' => 'fecha_cierre IS NULL OR fecha_cierre >= fecha_apertura',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.convocatoria_talento');
    }
};
