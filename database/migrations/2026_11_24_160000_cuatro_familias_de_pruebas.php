<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->integer('tiempo_limite_seg')->nullable()->after('limite_preguntas');
        });

        DB::statement('ALTER TABLE talento.prueba DROP CONSTRAINT IF EXISTS ck_prueba_familia');

        DB::table('talento.prueba')->where('familia', 'INTELIGENCIA_EMOCIONAL')->update([
            'familia' => 'HABILIDAD_BLANDA',
        ]);

        $renombres = [
            'TEST_PERSONALIDAD' => ['16PF_FORMA_A', '16PF Forma A', 'Cuestionario de 16 factores, forma A. Se rinden las 187 preguntas.'],
            'TEST_INTELIGENCIA' => ['ES_USTED_MUY_HABIL', '¿Es usted muy hábil?', 'Matrices de figuras. Se rinden las 15 preguntas.'],
            'TEST_INTELIGENCIA_EMOCIONAL' => ['TMMS_24', 'TMMS-24', 'Atención, claridad y reparación emocional. 24 preguntas.'],
        ];

        foreach ($renombres as $anterior => [$codigo, $nombre, $descripcion]) {
            DB::table('talento.prueba')->where('codigo', $anterior)->update([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
            ]);
        }

        DB::table('talento.prueba')->update(['limite_preguntas' => null]);
        DB::table('talento.proceso_prueba')->update(['limite_preguntas' => null]);

        $numericaId = DB::table('talento.prueba')->where('codigo', 'TEST_APTITUD_NUMERICA')->value('id');
        if ($numericaId !== null) {
            $usada = DB::table('talento.proceso_prueba')->where('prueba_id', $numericaId)->exists()
                || DB::table('talento.pregunta')->where('prueba_id', $numericaId)->exists()
                || DB::table('talento.prueba_carrera')->where('prueba_id', $numericaId)->exists();

            if ($usada) {
                DB::table('talento.prueba')->where('id', $numericaId)->update(['activa' => false]);
            } else {
                DB::table('talento.prueba')->where('id', $numericaId)->delete();
            }
        }

        DB::statement("ALTER TABLE talento.prueba ADD CONSTRAINT ck_prueba_familia CHECK (familia IS NULL OR familia IN ('PERSONALIDAD', 'INTELIGENCIA', 'HABILIDAD_BLANDA', 'HABILIDAD_ESPECIFICA'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE talento.prueba DROP CONSTRAINT IF EXISTS ck_prueba_familia');

        $revertir = [
            '16PF_FORMA_A' => 'TEST_PERSONALIDAD',
            'ES_USTED_MUY_HABIL' => 'TEST_INTELIGENCIA',
            'TMMS_24' => 'TEST_INTELIGENCIA_EMOCIONAL',
        ];

        foreach ($revertir as $codigo => $anterior) {
            DB::table('talento.prueba')->where('codigo', $codigo)->update(['codigo' => $anterior]);
        }

        DB::table('talento.prueba')->where('codigo', 'TEST_INTELIGENCIA_EMOCIONAL')->update([
            'familia' => 'INTELIGENCIA_EMOCIONAL',
        ]);

        DB::statement("ALTER TABLE talento.prueba ADD CONSTRAINT ck_prueba_familia CHECK (familia IS NULL OR familia IN ('PERSONALIDAD', 'INTELIGENCIA', 'INTELIGENCIA_EMOCIONAL', 'HABILIDAD_ESPECIFICA', 'HABILIDAD_BLANDA'))");

        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->dropColumn('tiempo_limite_seg');
        });
    }
};
