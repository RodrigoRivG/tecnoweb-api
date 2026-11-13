<?php

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
        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->smallInteger('limite_preguntas')->nullable()->after('descripcion')
                ->comment('Límite de preguntas aleatorias a presentar en el examen. NULL = usar todas.');
        });

        Schema::table('talento.proceso_prueba', function (Blueprint $table) {
            $table->smallInteger('limite_preguntas')->nullable()->after('max_intentos')
                ->comment('Sobrescribe la cantidad de preguntas aleatorias para esta convocatoria.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talento.proceso_prueba', function (Blueprint $table) {
            $table->dropColumn('limite_preguntas');
        });

        Schema::table('talento.prueba', function (Blueprint $table) {
            $table->dropColumn('limite_preguntas');
        });
    }
};
