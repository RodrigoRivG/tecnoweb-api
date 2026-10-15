<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vistas en orden de creación (v_ranking_provisional depende de v_puntaje_postulacion).
     *
     * @var list<string>
     */
    private array $vistas = [
        'v_proceso_pesos',
        'v_puntaje_postulacion',
        'v_ranking_provisional',
        'v_bandeja_tareas',
        'v_embudo_proceso',
        'v_perfil_publico',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- Suma de pesos por proceso; debe ser 100 antes de abrirlo
            CREATE VIEW talento.v_proceso_pesos AS
            SELECT pe.proceso_id,
                   ROUND(COALESCE(SUM(pp.peso), 0), 2)            AS suma_pesos,
                   (ROUND(COALESCE(SUM(pp.peso), 0), 2) = 100.00) AS pesos_validos
              FROM talento.proceso_etapa pe
              LEFT JOIN talento.proceso_prueba pp ON pp.proceso_etapa_id = pe.id
             WHERE pe.activa
             GROUP BY pe.proceso_id;

            -- Puntaje total ponderado por postulación (asume pesos que suman 100)
            CREATE VIEW talento.v_puntaje_postulacion AS
            SELECT po.id AS postulacion_id,
                   po.proceso_id,
                   ROUND(SUM(r.puntaje_normalizado * pp.peso) / 100, 2) AS puntaje_total,
                   COUNT(r.id)                                          AS pruebas_calificadas,
                   (SELECT COUNT(*)
                      FROM talento.proceso_prueba pp2
                      JOIN talento.proceso_etapa pe2 ON pe2.id = pp2.proceso_etapa_id
                     WHERE pe2.proceso_id = po.proceso_id AND pe2.activa) AS pruebas_configuradas
              FROM talento.postulacion po
              JOIN talento.resultado_prueba r ON r.postulacion_id = po.id
              JOIN talento.proceso_prueba pp  ON pp.id = r.proceso_prueba_id
             GROUP BY po.id, po.proceso_id;

            -- Ranking provisional (antes de publicarlo): RANK() permite ver empates
            CREATE VIEW talento.v_ranking_provisional AS
            SELECT vp.proceso_id,
                   vp.postulacion_id,
                   vp.puntaje_total,
                   RANK() OVER (PARTITION BY vp.proceso_id ORDER BY vp.puntaje_total DESC) AS posicion_calculada
              FROM talento.v_puntaje_postulacion vp;

            -- Bandeja de tareas manuales (análisis de CV, entrevista, antecedentes)
            CREATE VIEW talento.v_bandeja_tareas AS
            SELECT pt.id           AS tarea_id,
                   pt.asignado_a,
                   pt.estado,
                   po.codigo       AS codigo_candidato,
                   pr.codigo       AS codigo_proceso,
                   pr.tipo         AS tipo_proceso,
                   e.codigo        AS etapa,
                   pt.asignado_en,
                   FLOOR(EXTRACT(EPOCH FROM (now() - COALESCE(pt.asignado_en, pt.iniciada_en, pt.updated_at))) / 86400)::integer AS dias_pendiente
              FROM talento.postulacion_etapa pt
              JOIN talento.postulacion   po ON po.id = pt.postulacion_id
              JOIN talento.proceso       pr ON pr.id = po.proceso_id
              JOIN talento.proceso_etapa pe ON pe.id = pt.proceso_etapa_id
              JOIN talento.etapa         e  ON e.id  = pe.etapa_id
             WHERE e.ejecucion = 'MANUAL'
               AND pt.estado IN ('PENDIENTE','EN_REVISION');

            -- Embudo del dashboard: candidatos por etapa y estado
            CREATE VIEW talento.v_embudo_proceso AS
            SELECT pe.proceso_id,
                   pe.orden,
                   e.codigo AS etapa,
                   e.nombre AS etapa_nombre,
                   COUNT(*) FILTER (WHERE pt.estado = 'APROBADA')                              AS aprobadas,
                   COUNT(*) FILTER (WHERE pt.estado IN ('PENDIENTE','EN_CURSO','EN_REVISION')) AS en_curso,
                   COUNT(*) FILTER (WHERE pt.estado = 'REPROBADA')                             AS reprobadas
              FROM talento.proceso_etapa pe
              JOIN talento.etapa e ON e.id = pe.etapa_id
              LEFT JOIN talento.postulacion_etapa pt ON pt.proceso_etapa_id = pe.id
             GROUP BY pe.id, pe.proceso_id, pe.orden, e.codigo, e.nombre;

            -- Única fuente de datos de la landing pública: sin nombre, CI, contacto ni documentos
            CREATE VIEW talento.v_perfil_publico AS
            SELECT rr.id                AS ranking_id,
                   po.codigo            AS codigo_candidato,
                   f.id                 AS facultad_id,
                   f.nombre             AS facultad,
                   c.id                 AS carrera_id,
                   c.nombre             AS carrera,
                   ct.gestion,
                   eg.anio_egreso,
                   rr.posicion,
                   rr.puntaje_total,
                   rr.es_top,
                   rr.publicado_en
              FROM talento.ranking_resultado rr
              JOIN talento.postulacion po          ON po.id = rr.postulacion_id
              JOIN talento.convocatoria_talento ct ON ct.proceso_id = rr.proceso_id
              JOIN talento.carrera c               ON c.id = ct.carrera_id
              JOIN talento.facultad f              ON f.id = c.facultad_id
              JOIN talento.egresado eg             ON eg.persona_id = po.persona_id AND eg.carrera_id = ct.carrera_id
             WHERE rr.visible
               AND rr.publicado_en IS NOT NULL
               AND rr.publicado_en + make_interval(months => ct.vigencia_perfil_meses) >= now()
               AND COALESCE((SELECT cs.otorgado
                               FROM talento.consentimiento cs
                              WHERE cs.persona_id = po.persona_id AND cs.tipo = 'PUBLICACION_PERFIL'
                              ORDER BY cs.registrado_en DESC, cs.id DESC
                              LIMIT 1), FALSE);
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->vistas) as $vista) {
            DB::statement("DROP VIEW IF EXISTS talento.{$vista}");
        }
    }
};
