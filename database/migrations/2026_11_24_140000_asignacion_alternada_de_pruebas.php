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
        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->string('familia', 24)->nullable()->after('tipo');
        });

        Schema::table('talento.proceso_prueba', function (Blueprint $table) {
            $table->string('modo_asignacion', 10)->default('FIJA')->after('prueba_id');
        });

        DB::statement("ALTER TABLE talento.prueba ADD CONSTRAINT ck_prueba_familia CHECK (familia IS NULL OR familia IN ('PERSONALIDAD', 'INTELIGENCIA', 'INTELIGENCIA_EMOCIONAL', 'HABILIDAD_ESPECIFICA', 'HABILIDAD_BLANDA'))");
        DB::statement("ALTER TABLE talento.proceso_prueba ADD CONSTRAINT ck_proceso_prueba_modo CHECK (modo_asignacion IN ('FIJA', 'ALTERNA'))");

        $familias = [
            'TEST_PERSONALIDAD' => 'PERSONALIDAD',
            'TEST_INTELIGENCIA' => 'INTELIGENCIA',
            'TEST_INTELIGENCIA_EMOCIONAL' => 'INTELIGENCIA_EMOCIONAL',
            'TEST_LECTURA_COMPRENSIVA' => 'HABILIDAD_ESPECIFICA',
            'TEST_PURDUE_NO_VERBAL' => 'HABILIDAD_ESPECIFICA',
            'TEST_APTITUD_NUMERICA' => 'HABILIDAD_ESPECIFICA',
            'TEST_HABILIDADES_BLANDAS' => 'HABILIDAD_BLANDA',
        ];

        foreach ($familias as $codigo => $familia) {
            DB::table('talento.prueba')->where('codigo', $codigo)->update(['familia' => $familia]);
        }

        DB::statement("
            UPDATE talento.proceso_prueba AS cupo
               SET modo_asignacion = 'ALTERNA'
              FROM talento.prueba AS prueba
             WHERE prueba.id = cupo.prueba_id
               AND prueba.familia IN ('PERSONALIDAD', 'INTELIGENCIA', 'INTELIGENCIA_EMOCIONAL', 'HABILIDAD_BLANDA')
        ");

        Schema::create('talento.prueba_carrera', function (Blueprint $table) {
            $table->integer('prueba_id');
            $table->integer('carrera_id');
            Talento::timestamps($table);

            $table->primary(['prueba_id', 'carrera_id']);
            $table->foreign('prueba_id', 'fk_prueba_carrera_prueba')->references('id')->on('talento.prueba')->cascadeOnDelete();
            $table->foreign('carrera_id', 'fk_prueba_carrera_carrera')->references('id')->on('talento.carrera')->cascadeOnDelete();
            $table->index('carrera_id', 'ix_prueba_carrera_carrera');
            $table->comment('Pruebas de habilidades específicas que corresponden a una carrera');
        });

        Talento::finalizar('prueba_carrera');

        Schema::create('talento.asignacion_prueba', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->bigInteger('proceso_prueba_id');
            $table->integer('prueba_id');
            Talento::timestamps($table);

            $table->unique(['postulacion_id', 'proceso_prueba_id'], 'uq_asignacion_postulacion_cupo');
            $table->foreign('postulacion_id', 'fk_asignacion_postulacion')->references('id')->on('talento.postulacion');
            $table->foreign('proceso_prueba_id', 'fk_asignacion_proceso_prueba')->references('id')->on('talento.proceso_prueba');
            $table->foreign('prueba_id', 'fk_asignacion_prueba')->references('id')->on('talento.prueba');
            $table->index('proceso_prueba_id', 'ix_asignacion_proceso_prueba');
            $table->index('prueba_id', 'ix_asignacion_prueba');
            $table->comment('Instrumento concreto que le tocó rendir a un postulante en un cupo del proceso');
        });

        Talento::finalizar('asignacion_prueba');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.asignacion_prueba');
        Schema::dropIfExists('talento.prueba_carrera');

        DB::statement('ALTER TABLE talento.proceso_prueba DROP CONSTRAINT IF EXISTS ck_proceso_prueba_modo');
        DB::statement('ALTER TABLE talento.prueba DROP CONSTRAINT IF EXISTS ck_prueba_familia');

        Schema::table('talento.proceso_prueba', function (Blueprint $table) {
            $table->dropColumn('modo_asignacion');
        });

        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->dropColumn('familia');
        });
    }
};
