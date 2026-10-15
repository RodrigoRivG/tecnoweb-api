<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Triggers de negocio: reglas entre tablas que una FK no puede expresar.
     *
     * @var array<string, array{tabla: string, momento: string, funcion: string}>
     */
    private array $triggers = [
        'trg_postulacion_bi' => ['tabla' => 'postulacion', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_postulacion_bi'],
        'trg_postulacion_etapa_bi' => ['tabla' => 'postulacion_etapa', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_postulacion_etapa_bi'],
        'trg_postulacion_etapa_ai' => ['tabla' => 'postulacion_etapa', 'momento' => 'AFTER INSERT', 'funcion' => 'fn_postulacion_etapa_hist'],
        'trg_postulacion_etapa_au' => ['tabla' => 'postulacion_etapa', 'momento' => 'AFTER UPDATE', 'funcion' => 'fn_postulacion_etapa_hist'],
        'trg_intento_prueba_bi' => ['tabla' => 'intento_prueba', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_intento_prueba_bi'],
        'trg_respuesta_intento_bi' => ['tabla' => 'respuesta_intento', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_respuesta_intento_bi'],
        'trg_resultado_prueba_bi' => ['tabla' => 'resultado_prueba', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_resultado_prueba_bi'],
        'trg_calificacion_criterio_bi' => ['tabla' => 'calificacion_criterio', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_calificacion_criterio_bi'],
        'trg_ranking_resultado_bi' => ['tabla' => 'ranking_resultado', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_ranking_resultado_bi'],
        'trg_informe_candidato_bi' => ['tabla' => 'informe_candidato', 'momento' => 'BEFORE INSERT', 'funcion' => 'fn_informe_candidato_bi'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- Una postulación debe ser coherente con el tipo de proceso
            CREATE OR REPLACE FUNCTION talento.fn_postulacion_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            DECLARE
              v_tipo    VARCHAR(10);
              v_carrera INTEGER;
            BEGIN
              SELECT tipo INTO v_tipo FROM proceso WHERE id = NEW.proceso_id;

              IF v_tipo = 'TALENTO' THEN
                SELECT carrera_id INTO v_carrera FROM convocatoria_talento WHERE proceso_id = NEW.proceso_id;
                IF NOT EXISTS (SELECT 1 FROM egresado e WHERE e.persona_id = NEW.persona_id AND e.carrera_id = v_carrera) THEN
                  RAISE EXCEPTION 'La persona no es egresada de la carrera de esta convocatoria';
                END IF;
                IF NEW.registrada_por IS NOT NULL THEN
                  RAISE EXCEPTION 'Una postulación de talento no puede ser registrada por una empresa';
                END IF;
              ELSIF v_tipo = 'EMPRESA' THEN
                IF NEW.registrada_por IS NULL THEN
                  RAISE EXCEPTION 'Una postulación de empresa debe indicar el contacto que la registró';
                END IF;
                IF NOT EXISTS (
                  SELECT 1
                    FROM solicitud_empresa s
                    JOIN contacto_empresa solicitante ON solicitante.id = s.contacto_id
                    JOIN contacto_empresa registrador ON registrador.id = NEW.registrada_por
                                                     AND registrador.empresa_id = solicitante.empresa_id
                   WHERE s.proceso_id = NEW.proceso_id
                ) THEN
                  RAISE EXCEPTION 'El contacto no pertenece a la empresa que hizo la solicitud';
                END IF;
              END IF;
              RETURN NEW;
            END $$;

            -- La etapa debe pertenecer al mismo proceso que la postulación
            CREATE OR REPLACE FUNCTION talento.fn_postulacion_etapa_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
                 IS DISTINCT FROM (SELECT proceso_id FROM proceso_etapa WHERE id = NEW.proceso_etapa_id) THEN
                RAISE EXCEPTION 'La etapa no pertenece al proceso de la postulación';
              END IF;
              RETURN NEW;
            END $$;

            -- Historial de estados de etapa (el usuario se toma de la variable de sesión app.usuario_id)
            CREATE OR REPLACE FUNCTION talento.fn_postulacion_etapa_hist() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            DECLARE
              v_usuario BIGINT := NULLIF(current_setting('app.usuario_id', true), '')::bigint;
            BEGIN
              IF TG_OP = 'INSERT' THEN
                INSERT INTO historial_postulacion_etapa (postulacion_etapa_id, estado_anterior, estado_nuevo, usuario_id, motivo)
                VALUES (NEW.id, NULL, NEW.estado, v_usuario, NEW.motivo);
              ELSIF OLD.estado IS DISTINCT FROM NEW.estado THEN
                INSERT INTO historial_postulacion_etapa (postulacion_etapa_id, estado_anterior, estado_nuevo, usuario_id, motivo)
                VALUES (NEW.id, OLD.estado, NEW.estado, v_usuario, NEW.motivo);
              END IF;
              RETURN NULL;
            END $$;

            -- El intento debe ser de una prueba TEST del mismo proceso y no superar los intentos permitidos
            CREATE OR REPLACE FUNCTION talento.fn_intento_prueba_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT p.tipo FROM proceso_prueba pp JOIN prueba p ON p.id = pp.prueba_id
                   WHERE pp.id = NEW.proceso_prueba_id) IS DISTINCT FROM 'TEST' THEN
                RAISE EXCEPTION 'Solo las pruebas de tipo TEST se rinden en intentos';
              END IF;
              IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
                 IS DISTINCT FROM (SELECT pe.proceso_id FROM proceso_prueba pp JOIN proceso_etapa pe ON pe.id = pp.proceso_etapa_id
                                    WHERE pp.id = NEW.proceso_prueba_id) THEN
                RAISE EXCEPTION 'La prueba no pertenece al proceso de la postulación';
              END IF;
              IF NEW.nro_intento > (SELECT max_intentos FROM proceso_prueba WHERE id = NEW.proceso_prueba_id) THEN
                RAISE EXCEPTION 'Se superó el número máximo de intentos de la prueba';
              END IF;
              RETURN NEW;
            END $$;

            -- La pregunta respondida debe ser del test que se está rindiendo
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

            -- El resultado debe corresponder a una prueba del mismo proceso que la postulación
            CREATE OR REPLACE FUNCTION talento.fn_resultado_prueba_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
                 IS DISTINCT FROM (SELECT pe.proceso_id FROM proceso_prueba pp JOIN proceso_etapa pe ON pe.id = pp.proceso_etapa_id
                                    WHERE pp.id = NEW.proceso_prueba_id) THEN
                RAISE EXCEPTION 'La prueba no pertenece al proceso de la postulación';
              END IF;
              RETURN NEW;
            END $$;

            -- El criterio calificado debe ser de la rúbrica de la prueba evaluada
            CREATE OR REPLACE FUNCTION talento.fn_calificacion_criterio_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT prueba_id FROM rubrica_criterio WHERE id = NEW.criterio_id)
                 IS DISTINCT FROM (SELECT pp.prueba_id FROM resultado_prueba r JOIN proceso_prueba pp ON pp.id = r.proceso_prueba_id
                                    WHERE r.id = NEW.resultado_prueba_id) THEN
                RAISE EXCEPTION 'El criterio no pertenece a la rúbrica de la prueba';
              END IF;
              RETURN NEW;
            END $$;

            -- El ranking solo puede incluir postulaciones del mismo proceso
            CREATE OR REPLACE FUNCTION talento.fn_ranking_resultado_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id) IS DISTINCT FROM NEW.proceso_id THEN
                RAISE EXCEPTION 'La postulación no pertenece al proceso del ranking';
              END IF;
              RETURN NEW;
            END $$;

            -- El informe solo puede dictaminar candidatos de su propio proceso
            CREATE OR REPLACE FUNCTION talento.fn_informe_candidato_bi() RETURNS trigger
              LANGUAGE plpgsql SET search_path = talento AS $$
            BEGIN
              IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
                 IS DISTINCT FROM (SELECT proceso_id FROM informe_final WHERE id = NEW.informe_id) THEN
                RAISE EXCEPTION 'La postulación no pertenece al proceso del informe';
              END IF;
              RETURN NEW;
            END $$;
            SQL);

        foreach ($this->triggers as $nombre => $trigger) {
            DB::statement("CREATE TRIGGER {$nombre} {$trigger['momento']} ON talento.{$trigger['tabla']} FOR EACH ROW EXECUTE FUNCTION talento.{$trigger['funcion']}()");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->triggers as $nombre => $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$nombre} ON talento.{$trigger['tabla']}");
        }

        foreach (array_unique(array_column($this->triggers, 'funcion')) as $funcion) {
            DB::statement("DROP FUNCTION IF EXISTS talento.{$funcion}()");
        }
    }
};
