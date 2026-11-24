<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talento.configuracion_familia', function (Blueprint $table) {
            $table->string('familia', 24)->primary();
            $table->integer('tiempo_pregunta_seg')->default(60);
            Talento::timestamps($table);
            $table->comment('Segundos que dura cada pregunta de los libros de esa familia');
        });

        Talento::finalizar('configuracion_familia', [
            'ck_configuracion_familia_codigo' => Talento::en('familia', ['PERSONALIDAD', 'INTELIGENCIA', 'HABILIDAD_BLANDA', 'HABILIDAD_ESPECIFICA']),
            'ck_configuracion_familia_tiempo' => 'tiempo_pregunta_seg BETWEEN 5 AND 3600',
        ]);

        $ahora = now();
        DB::table('talento.configuracion_familia')->insert([
            ['familia' => 'PERSONALIDAD', 'tiempo_pregunta_seg' => 60, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['familia' => 'INTELIGENCIA', 'tiempo_pregunta_seg' => 60, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['familia' => 'HABILIDAD_BLANDA', 'tiempo_pregunta_seg' => 60, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['familia' => 'HABILIDAD_ESPECIFICA', 'tiempo_pregunta_seg' => 60, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        Schema::table('talento.intento_prueba', function (Blueprint $table) {
            $table->timestampTz('pregunta_actual_iniciada_en')->nullable()->after('iniciado_en');
        });

        Schema::table('talento.respuesta_intento', function (Blueprint $table) {
            $table->boolean('expirada')->default(false)->after('opcion_id');
        });
    }

    public function down(): void
    {
        Schema::table('talento.respuesta_intento', function (Blueprint $table) {
            $table->dropColumn('expirada');
        });

        Schema::table('talento.intento_prueba', function (Blueprint $table) {
            $table->dropColumn('pregunta_actual_iniciada_en');
        });

        Schema::dropIfExists('talento.configuracion_familia');
    }
};
