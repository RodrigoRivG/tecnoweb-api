<?php

use App\Support\Migraciones\Talento;
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
        Schema::create('talento.contacto_empresa', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('empresa_id');
            $table->bigInteger('persona_id');
            $table->string('cargo', 100);
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            Talento::timestamps($table);

            $table->unique('persona_id', 'uq_contacto_persona');
            $table->foreign('empresa_id', 'fk_contacto_empresa')->references('id')->on('talento.empresa');
            $table->foreign('persona_id', 'fk_contacto_persona')->references('id')->on('talento.persona');
            $table->index('empresa_id', 'ix_contacto_empresa');
            $table->comment('Usuarios de la empresa cliente (rol EMPRESA) y su cargo');
        });

        // Un solo contacto principal por empresa
        DB::statement('CREATE UNIQUE INDEX uq_contacto_principal ON talento.contacto_empresa (empresa_id) WHERE es_principal');

        Talento::finalizar('contacto_empresa');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.contacto_empresa');
    }
};
