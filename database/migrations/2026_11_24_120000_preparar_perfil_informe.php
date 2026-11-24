<?php

use App\Catalogos\DimensionesInforme;
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
        Schema::table('talento.dimension_evaluada', function (Blueprint $table) {
            $table->string('factor', 3)->nullable()->after('codigo');
            $table->string('polo_bajo', 80)->nullable()->after('nombre');
            $table->string('polo_alto', 80)->nullable()->after('polo_bajo');
        });

        Schema::table('talento.resultado_dimension', function (Blueprint $table) {
            $table->decimal('puntuacion_bruta', 7, 2)->nullable()->after('valor');
        });

        DB::statement("ALTER TABLE talento.dimension_evaluada ADD CONSTRAINT ck_dimension_factor CHECK (factor IS NULL OR factor IN ('A','B','C','E','F','G','H','I','L','M','N','O','Q1','Q2','Q3','Q4'))");
        DB::statement('CREATE UNIQUE INDEX uq_dimension_factor ON talento.dimension_evaluada (factor) WHERE factor IS NOT NULL');
        DB::statement('ALTER TABLE talento.resultado_dimension ADD CONSTRAINT ck_resultado_dimension_bruto CHECK (puntuacion_bruta IS NULL OR puntuacion_bruta >= 0)');

        foreach (DimensionesInforme::factores16Pf() as $factor) {
            DB::table('talento.dimension_evaluada')->where('codigo', $factor['codigo'])->update([
                'factor' => $factor['factor'],
                'nombre' => $factor['nombre'],
                'polo_bajo' => $factor['polo_bajo'],
                'polo_alto' => $factor['polo_alto'],
                'orden' => $factor['orden'],
            ]);
        }

        foreach (DimensionesInforme::habilidadesBlandas() as $dimension) {
            $existe = DB::table('talento.dimension_evaluada')->where('codigo', $dimension['codigo'])->exists();
            if ($existe) {
                DB::table('talento.dimension_evaluada')->where('codigo', $dimension['codigo'])->update([
                    'nombre' => $dimension['nombre'],
                    'tipo' => 'HABILIDAD_BLANDA',
                    'orden' => $dimension['orden'],
                ]);

                continue;
            }

            DB::table('talento.dimension_evaluada')->insert([
                'codigo' => $dimension['codigo'],
                'nombre' => $dimension['nombre'],
                'tipo' => 'HABILIDAD_BLANDA',
                'orden' => $dimension['orden'],
            ]);
        }

        $entrevistaId = DB::table('talento.prueba')->where('codigo', 'ENTREVISTA')->value('id');
        if ($entrevistaId) {
            $criterios = [
                1 => 'Presencia',
                2 => 'Vocabulario',
                3 => 'Seguridad',
                4 => 'Conocimientos técnicos',
            ];
            foreach ($criterios as $orden => $nombre) {
                DB::table('talento.rubrica_criterio')
                    ->where('prueba_id', $entrevistaId)
                    ->where('orden', $orden)
                    ->update([
                        'nombre' => $nombre,
                        'puntaje_maximo' => 10,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE talento.resultado_dimension DROP CONSTRAINT IF EXISTS ck_resultado_dimension_bruto');
        DB::statement('DROP INDEX IF EXISTS talento.uq_dimension_factor');
        DB::statement('ALTER TABLE talento.dimension_evaluada DROP CONSTRAINT IF EXISTS ck_dimension_factor');

        Schema::table('talento.resultado_dimension', function (Blueprint $table) {
            $table->dropColumn('puntuacion_bruta');
        });

        Schema::table('talento.dimension_evaluada', function (Blueprint $table) {
            $table->dropColumn(['factor', 'polo_bajo', 'polo_alto']);
        });
    }
};
