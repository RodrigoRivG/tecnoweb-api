<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca qué roles entran al panel y vuelve a guardar las carreras de quienes tienen alcance limitado.
     */
    public function up(): void
    {
        Schema::table('talento.rol', function (Blueprint $table) {
            $table->boolean('es_panel')->default(false);
            $table->boolean('alcance_carrera')->default(false);
        });

        DB::table('talento.rol')->whereIn('codigo', ['ADMIN', 'AYUDANTE'])->update(['es_panel' => true]);
        DB::table('talento.rol')->where('codigo', 'AYUDANTE')->update(['alcance_carrera' => true]);

        Schema::create('talento.ayudante_carrera', function (Blueprint $table) {
            $table->bigInteger('usuario_id');
            $table->integer('carrera_id');
            Talento::timestamps($table);

            $table->primary(['usuario_id', 'carrera_id']);
            $table->foreign('usuario_id', 'fk_ayudante_usuario')->references('id')->on('talento.usuario')->cascadeOnDelete();
            $table->foreign('carrera_id', 'fk_ayudante_carrera')->references('id')->on('talento.carrera');
            $table->index('carrera_id', 'ix_ayudante_carrera_carrera');
            $table->comment('Carreras que atiende un usuario cuyo rol tiene alcance por carrera');
        });

        Talento::finalizar('ayudante_carrera');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.ayudante_carrera');

        Schema::table('talento.rol', function (Blueprint $table) {
            $table->dropColumn(['es_panel', 'alcance_carrera']);
        });
    }
};
