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
        Schema::create('talento.persona', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('usuario_id')->nullable();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 60)->nullable();
            $table->string('apellido_materno', 60)->nullable();
            $table->string('ci_numero', 15);
            $table->string('ci_complemento', 3)->default('');
            $table->string('ci_expedido', 10)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('telefono', 20)->nullable();
            $table->string('correo_contacto', 190)->nullable();
            Talento::timestamps($table);

            $table->unique('usuario_id', 'uq_persona_usuario');
            $table->unique(['ci_numero', 'ci_complemento'], 'uq_persona_ci');
            $table->foreign('usuario_id', 'fk_persona_usuario')->references('id')->on('talento.usuario');
            $table->index(['apellido_paterno', 'apellido_materno', 'nombres'], 'ix_persona_apellidos');
            $table->comment('Identidad de toda persona (egresado, candidato externo, contacto, personal). usuario_id es NULL hasta que acepta la invitación');
        });

        Talento::finalizar('persona', [
            'ck_persona_apellido' => 'apellido_paterno IS NOT NULL OR apellido_materno IS NOT NULL',
            'ck_persona_ci_expedido' => Talento::en('ci_expedido', ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.persona');
    }
};
