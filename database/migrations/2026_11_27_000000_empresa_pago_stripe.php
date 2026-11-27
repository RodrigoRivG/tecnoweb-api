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
        DB::statement('ALTER TABLE talento.proceso DROP CONSTRAINT IF EXISTS ck_proceso_estado');
        DB::statement(
            'ALTER TABLE talento.proceso ADD CONSTRAINT ck_proceso_estado CHECK ('
            .Talento::en('estado', [
                'BORRADOR', 'CONFIGURACION', 'PENDIENTE_APROBACION', 'PENDIENTE_PAGO', 'PENDIENTE_CANDIDATOS',
                'EN_CURSO', 'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'INFORME_EMITIDO', 'CANCELADO',
            ])
            .')'
        );

        Schema::create('talento.pago_proceso', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->bigInteger('proceso_id');
            $table->string('stripe_checkout_session_id', 255)->nullable();
            $table->string('stripe_payment_intent_id', 255)->nullable();
            $table->integer('monto_centavos');
            $table->string('moneda', 3)->default('usd');
            $table->string('estado', 20);
            $table->timestampTz('pagado_en')->nullable();
            Talento::timestamps($table);

            $table->unique('proceso_id', 'uq_pago_proceso');
            $table->unique('stripe_checkout_session_id', 'uq_pago_stripe_session');
            $table->foreign('proceso_id', 'fk_pago_proceso')->references('id')->on('talento.proceso');
        });

        Talento::finalizar('pago_proceso', [
            'ck_pago_estado' => Talento::en('estado', ['PENDIENTE', 'COMPLETADO', 'FALLIDO', 'SIMULADO']),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('talento.pago_proceso');

        DB::statement('ALTER TABLE talento.proceso DROP CONSTRAINT IF EXISTS ck_proceso_estado');
        DB::statement(
            'ALTER TABLE talento.proceso ADD CONSTRAINT ck_proceso_estado CHECK ('
            .Talento::en('estado', [
                'BORRADOR', 'CONFIGURACION', 'PENDIENTE_APROBACION', 'EN_CURSO',
                'ABIERTO', 'EN_EVALUACION', 'CERRADO', 'INFORME_EMITIDO', 'CANCELADO',
            ])
            .')'
        );
    }
};