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
        Schema::create('talento.permiso', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 60);
            $table->string('nombre', 120);
            $table->string('modulo', 60);
            $table->string('descripcion', 255)->nullable();
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_permiso_codigo');
            $table->comment('Catálogo de permisos del sistema agrupados por módulo');
        });

        Talento::finalizar('permiso');

        Schema::create('talento.rol_permiso', function (Blueprint $table) {
            $table->integer('rol_id');
            $table->integer('permiso_id');
            Talento::timestamps($table);

            $table->primary(['rol_id', 'permiso_id']);
            $table->foreign('rol_id', 'fk_rol_permiso_rol')->references('id')->on('talento.rol')->cascadeOnDelete();
            $table->foreign('permiso_id', 'fk_rol_permiso_permiso')->references('id')->on('talento.permiso')->cascadeOnDelete();
            $table->comment('Asignación de permisos por rol (RBAC)');
        });

        Talento::finalizar('rol_permiso');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.rol_permiso');
        Schema::dropIfExists('talento.permiso');
    }
};

