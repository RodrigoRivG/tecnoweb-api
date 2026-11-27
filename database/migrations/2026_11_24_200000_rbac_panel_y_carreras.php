<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca qué roles entran al panel. El personal interno (ayudante) no se limita por carrera académica.
     */
    public function up(): void
    {
        Schema::table('talento.rol', function (Blueprint $table) {
            $table->boolean('es_panel')->default(false);
            $table->boolean('alcance_carrera')->default(false);
        });

        DB::table('talento.rol')->whereIn('codigo', ['ADMIN', 'AYUDANTE'])->update(['es_panel' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talento.rol', function (Blueprint $table) {
            $table->dropColumn(['es_panel', 'alcance_carrera']);
        });
    }
};
