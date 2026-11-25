<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La pregunta respondida pertenece al libro asignado al postulante,
     * que puede ser distinto del libro ancla del cupo.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION talento.fn_respuesta_intento_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            DECLARE
              v_prueba integer;
            BEGIN
              SELECT a.prueba_id INTO v_prueba
                FROM intento_prueba i
                JOIN asignacion_prueba a
                  ON a.postulacion_id = i.postulacion_id
                 AND a.proceso_prueba_id = i.proceso_prueba_id
               WHERE i.id = NEW.intento_id;

              IF v_prueba IS NULL THEN
                SELECT pp.prueba_id INTO v_prueba
                  FROM intento_prueba i
                  JOIN proceso_prueba pp ON pp.id = i.proceso_prueba_id
                 WHERE i.id = NEW.intento_id;
              END IF;

              IF (SELECT prueba_id FROM pregunta WHERE id = NEW.pregunta_id) IS DISTINCT FROM v_prueba THEN
                RAISE EXCEPTION 'La pregunta no pertenece a la prueba del intento';
              END IF;
              RETURN NEW;
            END $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION talento.fn_respuesta_intento_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT prueba_id FROM pregunta WHERE id = NEW.pregunta_id)
                 IS DISTINCT FROM (SELECT pp.prueba_id FROM intento_prueba i JOIN proceso_prueba pp ON pp.id = i.proceso_prueba_id
                                    WHERE i.id = NEW.intento_id) THEN
                RAISE EXCEPTION 'La pregunta no pertenece a la prueba del intento';
              END IF;
              RETURN NEW;
            END $$;
        SQL);
    }
};
