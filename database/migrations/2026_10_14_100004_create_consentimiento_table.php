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
        Schema::create('talento.consentimiento', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('persona_id');
            $table->string('tipo', 26);
            $table->string('version_politica', 20);
            $table->boolean('otorgado');
            $table->timestampTz('registrado_en')->useCurrent();
            $table->ipAddress('ip_origen')->nullable();
            Talento::timestamps($table);

            $table->foreign('persona_id', 'fk_consentimiento_persona')->references('id')->on('talento.persona');
            $table->index(['persona_id', 'tipo', 'registrado_en'], 'ix_consentimiento_persona_tipo');
            $table->comment('Registro histórico (solo inserciones) de consentimientos y revocaciones. El estado vigente es la última fila por persona y tipo');
        });

        Talento::finalizar('consentimiento', [
            'ck_consentimiento_tipo' => Talento::en('tipo', ['TRATAMIENTO_DATOS', 'PUBLICACION_PERFIL', 'INVESTIGACION_ANTECEDENTES']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.consentimiento');
    }
};
