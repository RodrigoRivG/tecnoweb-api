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
        Schema::create('talento.archivo', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('nombre_original', 255);
            $table->string('clave_almacenamiento', 400);
            $table->string('mime_type', 100);
            $table->bigInteger('tamano_bytes');
            $table->char('hash_sha256', 64);
            $table->bigInteger('subido_por')->nullable();
            Talento::timestamps($table);

            $table->unique('clave_almacenamiento', 'uq_archivo_clave');
            $table->foreign('subido_por', 'fk_archivo_subido_por')->references('id')->on('talento.usuario')->nullOnDelete();
            $table->index('hash_sha256', 'ix_archivo_hash');
            $table->index('subido_por', 'ix_archivo_subido_por');
            $table->comment('Metadatos de archivos. El contenido vive en disco u object storage, nunca como BLOB');
        });

        Talento::finalizar('archivo', [
            'ck_archivo_tamano' => 'tamano_bytes > 0',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talento.archivo');
    }
};
