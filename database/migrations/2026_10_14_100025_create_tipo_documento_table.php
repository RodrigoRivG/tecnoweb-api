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
        Schema::create('talento.tipo_documento', function (Blueprint $table) {
            $table->increments('id')->generatedAs()->always();
            $table->string('codigo', 40);
            $table->string('nombre', 100);
            Talento::timestamps($table);

            $table->unique('codigo', 'uq_tipo_documento_codigo');
            $table->comment('Catálogo de documentos solicitables (histórico, cédula, foto, CV)');
        });

        Talento::finalizar('tipo_documento');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.tipo_documento');
    }
};
