<?php

use App\Support\Migraciones\Talento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE talento.proceso ALTER COLUMN estado TYPE varchar(30)');
        DB::statement('ALTER TABLE talento.proceso DROP CONSTRAINT IF EXISTS ck_proceso_estado');
        DB::statement(
            'ALTER TABLE talento.proceso ADD CONSTRAINT ck_proceso_estado CHECK ('
            .Talento::en('estado', [
                'BORRADOR', 'CONFIGURACION', 'PENDIENTE_APROBACION', 'EN_CURSO',
                'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'INFORME_EMITIDO', 'CANCELADO',
            ])
            .')'
        );

        Schema::table('talento.solicitud_empresa', function (Blueprint $table) {
            $table->integer('carrera_id')->nullable();
            $table->foreign('carrera_id', 'fk_solicitud_carrera')->references('id')->on('talento.carrera');
        });

        Schema::table('talento.invitacion_postulacion', function (Blueprint $table) {
            $table->string('clave_temporal', 40)->nullable();
        });

        Schema::create('talento.candidato_solicitud', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_id');
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 60);
            $table->string('apellido_materno', 60)->nullable();
            $table->string('ci_numero', 15);
            $table->date('fecha_nacimiento');
            $table->string('correo', 190);
            Talento::timestamps($table);

            $table->unique(['proceso_id', 'ci_numero'], 'uq_candidato_solicitud_ci');
            $table->unique(['proceso_id', 'correo'], 'uq_candidato_solicitud_correo');
            $table->foreign('proceso_id', 'fk_candidato_solicitud_proceso')->references('id')->on('talento.proceso');
            $table->index('correo', 'ix_candidato_solicitud_correo');
            $table->comment('Lista cerrada cargada por la empresa, antes de crear las cuentas');
        });

        Talento::finalizar('candidato_solicitud');
    }

    public function down(): void
    {
        Schema::dropIfExists('talento.candidato_solicitud');

        Schema::table('talento.invitacion_postulacion', function (Blueprint $table) {
            $table->dropColumn('clave_temporal');
        });

        Schema::table('talento.solicitud_empresa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
        });

        DB::statement('ALTER TABLE talento.proceso DROP CONSTRAINT IF EXISTS ck_proceso_estado');
        DB::statement(
            'ALTER TABLE talento.proceso ADD CONSTRAINT ck_proceso_estado CHECK ('
            .Talento::en('estado', [
                'BORRADOR', 'CONFIGURACION', 'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'INFORME_EMITIDO', 'CANCELADO',
            ])
            .')'
        );
    }
};
