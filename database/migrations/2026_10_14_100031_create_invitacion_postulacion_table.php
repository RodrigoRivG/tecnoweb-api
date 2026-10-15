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
        Schema::create('talento.invitacion_postulacion', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('postulacion_id');
            $table->char('token_hash', 64);
            $table->string('estado', 10)->default('ENVIADA');
            $table->timestampTz('enviada_en')->useCurrent();
            $table->timestampTz('expira_en');
            $table->timestampTz('aceptada_en')->nullable();
            $table->smallInteger('reenvios')->default(0);
            Talento::timestamps($table);

            $table->unique('postulacion_id', 'uq_invitacion_postulacion');
            $table->unique('token_hash', 'uq_invitacion_token');
            $table->foreign('postulacion_id', 'fk_invitacion_postulacion')->references('id')->on('talento.postulacion');
            $table->comment('Invitación por correo al candidato externo de una empresa. Se guarda solo el hash del token');
        });

        Talento::finalizar('invitacion_postulacion', [
            'ck_invitacion_estado' => Talento::en('estado', ['ENVIADA', 'ACEPTADA', 'EXPIRADA', 'REVOCADA']),
            'ck_invitacion_vigencia' => 'expira_en > enviada_en',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.invitacion_postulacion');
    }
};
