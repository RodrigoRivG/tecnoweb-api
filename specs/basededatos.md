# Base de datos — Sistema de selección de personal (PostgreSQL)

> **Referencia de diseño, superada por las migraciones** (`database/migrations/2026_10_14_1000*`). El script de abajo ya no se ejecuta. Diferencias aplicadas, según [02_formato_tablas_bd.md](02_formato_tablas_bd.md) §4.3:
> - Las 43 tablas tienen `created_at` y `updated_at` con trigger `trg_<tabla>_updated`; `creado_en` pasó a `created_at`.
> - `uq_usuario_correo` es un índice único sobre `lower(correo)`.
> - Todos los `CHECK` tienen nombre `ck_<tabla>_<regla>` y toda FK tiene índice.
> - `ck_resultado_motivo` corregido a `NOT aprobada OR motivo_no_aprobacion IS NULL` (el original exigía que una prueba **no** aprobada no tuviera motivo).
> - Las funciones de trigger fijan `SET search_path = talento`.

Versión PostgreSQL del esquema `talento_uagrm`: sistematización de herramientas métricas para la selección de personal (flujo A: talento universitario UAGRM · flujo B: candidatos de la empresa).

- **Motor:** PostgreSQL 14 o superior (probado en 16.15)
- **Contenido:** 43 tablas · 73 claves foráneas · 10 triggers de negocio (+8 de `updated_at`) · 6 vistas · datos iniciales
- **Verificación:** el script se ejecutó completo en un PostgreSQL 16 real, con el flujo A de punta a punta y pruebas negativas (reglas que deben rechazar datos inválidos).

## Cómo instalarlo

```bash
createdb talento
psql -d talento -v ON_ERROR_STOP=1 -f talento_uagrm_pg.sql
```

Todo queda en el esquema `talento`. En la aplicación, conéctate con `SET search_path = talento, public;` (o configura `search_path` en el rol de la conexión).

## Cómo funciona el historial de etapas

Los triggers de `postulacion_etapa` guardan en `historial_postulacion_etapa` quién cambió cada estado. El usuario se toma de una variable de sesión que la aplicación debe fijar dentro de la transacción:

```sql
BEGIN;
SET LOCAL app.usuario_id = '12';          -- id del usuario autenticado
UPDATE postulacion_etapa SET estado = 'APROBADA' WHERE id = 55;
COMMIT;
```

Si no se fija, `usuario_id` queda en `NULL`.

## Qué cambia respecto a la versión MySQL

| MySQL | PostgreSQL |
|---|---|
| `AUTO_INCREMENT` | `GENERATED ALWAYS AS IDENTITY` |
| `INT/BIGINT UNSIGNED`, `TINYINT` | `INTEGER` / `BIGINT` / `SMALLINT` |
| `TINYINT(1)` | `BOOLEAN` |
| `DATETIME` en UTC | `TIMESTAMPTZ` |
| `ENUM(...)` | `VARCHAR` + `CHECK (... IN (...))` |
| `ON UPDATE CURRENT_TIMESTAMP` | trigger `set_updated_at()` |
| Columnas generadas para "uno solo principal / vigente" | **índices únicos parciales** (`WHERE es_principal`, `WHERE es_vigente`) |
| `VARBINARY(16)` para IP | `INET` |
| Triggers con `SIGNAL SQLSTATE '45000'` | funciones PL/pgSQL con `RAISE EXCEPTION` |
| Variable `@app_usuario_id` | `SET LOCAL app.usuario_id` |
| `TIMESTAMPDIFF`, `DATE_ADD` | `EXTRACT(EPOCH ...)`, `make_interval(...)` |
| `SUM(cond)` en vistas | `COUNT(*) FILTER (WHERE cond)` |

El modelo no cambia: mismo motor único de evaluación (supertipo `proceso` con subtipos por FK compuesta), un solo rol por usuario (`usuario.rol_id`), sin `usuario_rol` ni `bitacora_auditoria`.

## Tablas y su finalidad

| Tabla | Finalidad |
|---|---|
| `rol` | Roles del sistema: administrador, ayudante, egresado, candidato externo, empresa. Un usuario tiene un solo rol |
| `usuario` | Credenciales de acceso. Un único login; cada usuario tiene exactamente un rol |
| `persona` | Identidad de toda persona (egresado, candidato externo, contacto, personal). usuario_id es NULL hasta que acepta la invitación |
| `consentimiento` | Registro histórico (solo inserciones) de consentimientos y revocaciones. El estado vigente es la última fila por persona y tipo |
| `archivo` | Metadatos de archivos. El contenido vive en disco u object storage, nunca como BLOB |
| `facultad` | Facultades UAGRM. ppa_minimo lo define la administración y rige la evaluación técnica del flujo A |
| `carrera` | Carreras de cada facultad |
| `area_preferencia` | Áreas de preferencia por carrera (ej. Derecho: penal, civil, laboral) |
| `ayudante_carrera` | Carreras que atiende cada junior/ayudante; define su bandeja de tareas manuales |
| `egresado` | Perfil académico por carrera de un egresado titulado. La facultad se deduce de la carrera |
| `empresa` | Empresas que contratan evaluaciones o consultan talento |
| `contacto_empresa` | Usuarios de la empresa cliente (rol EMPRESA) y su cargo |
| `etapa` | Catálogo de tipos de etapa. MANUAL genera una tarea en la bandeja del ayudante |
| `dimension_evaluada` | Factores de personalidad y habilidades medibles; base del gráfico perfil ideal vs real |
| `prueba` | Instrumentos puntuables reutilizables: TEST en línea, RUBRICA manual (CV, entrevista) o CALCULADA (PPA) |
| `rubrica_criterio` | Criterios de una prueba tipo RUBRICA (ej. CV: formación, experiencia, habilidades, presentación) |
| `pregunta` | Banco de preguntas de un TEST (texto y/o imagen de la figura) |
| `opcion_pregunta` | Opciones A–E. Cada opción aporta un puntaje: sirve para pruebas con respuesta correcta y para personalidad |
| `proceso` | Supertipo: motor común de evaluación para convocatorias de talento y solicitudes de empresa |
| `convocatoria_talento` | Subtipo flujo A: convocatoria por carrera y gestión. Su ranking alimenta la landing pública |
| `solicitud_empresa` | Subtipo flujo B: la empresa se deduce del contacto solicitante (3FN) |
| `proceso_etapa` | Etapas que componen un proceso, en orden, y si eliminan al candidato que no las supera |
| `proceso_prueba` | Configuración de cada prueba dentro de un proceso: peso, nota mínima, tiempos e intentos. umbral_origen=FACULTAD usa facultad.ppa_minimo |
| `perfil_requerido` | Perfil psicológico y de habilidades ideal de un proceso (valor 1–10 por dimensión) |
| `tipo_documento` | Catálogo de documentos solicitables (histórico, cédula, foto, CV) |
| `requisito_documento` | Documentos que cada proceso exige a sus postulantes |
| `postulacion` | Candidato dentro de un proceso. codigo es el identificador seudónimo que se muestra en la landing pública |
| `documento_postulacion` | Documentos subidos por el postulante y su estado de validación |
| `postulacion_etapa` | Máquina de estados: avance del candidato por cada etapa. Las filas MANUAL en revisión forman la bandeja de tareas |
| `historial_postulacion_etapa` | Quién cambió el estado de cada etapa y cuándo (lo alimentan los triggers de postulacion_etapa) |
| `invitacion_postulacion` | Invitación por correo al candidato externo de una empresa. Se guarda solo el hash del token |
| `intento_prueba` | Cada vez que un candidato rinde un test en línea; duracion_seg permite aplicar tiempo mínimo y máximo |
| `respuesta_intento` | Respuestas de un intento. El puntaje se calcula uniendo con opcion_pregunta (no se duplica) |
| `resultado_prueba` | Resultado final de una prueba para una postulación (test, rúbrica o calculada). Guarda el umbral con el que se juzgó |
| `calificacion_criterio` | Puntaje por criterio en revisiones manuales (CV, entrevista) |
| `resultado_dimension` | Valor real del candidato por dimensión (0–10); se compara con perfil_requerido para graficar ideal vs real |
| `antecedente_laboral` | Cada empleo declarado por el candidato y el resultado de verificarlo con la referencia |
| `investigacion_antecedentes` | Conclusión global de la investigación de antecedentes de un candidato (semáforo) |
| `ranking_resultado` | Ranking congelado al cierre del proceso. es_top marca el top-N; visible controla la publicación en la landing |
| `informe_final` | Informe y recomendaciones que recibe la empresa al terminar su evaluación |
| `informe_candidato` | Dictamen de la consultora por candidato dentro del informe |
| `solicitud_informacion` | Pedido público de una empresa para obtener datos de contacto de un perfil. La consultora aprueba y entrega |
| `parametro_sistema` | Parámetros globales editables por el administrador |

## Vistas

| Vista | Para qué sirve |
|---|---|
| `v_perfil_publico` | Única fuente de la landing pública: sin nombre, CI, contacto ni documentos; exige consentimiento y vigencia |
| `v_puntaje_postulacion` | Puntaje total ponderado por postulación |
| `v_ranking_provisional` | Ranking antes de publicarlo (con empates) |
| `v_proceso_pesos` | Verifica que los pesos del proceso sumen 100 |
| `v_bandeja_tareas` | Tareas manuales pendientes de los ayudantes |
| `v_embudo_proceso` | Candidatos por etapa para el dashboard |

## Script completo

```sql
-- =====================================================================
--  TALENTO UAGRM — Base de datos del sistema de selección de personal
--  Motor: PostgreSQL 14+ (probado en 16)   ·   Versión del esquema: 1.0
--  Flujo A: talento universitario  ·  Flujo B: candidatos de la empresa
--
--  Convenciones
--   · Tablas y columnas en snake_case, en singular y en español.
--   · PK: id BIGINT / INTEGER GENERATED ALWAYS AS IDENTITY (catálogos: INTEGER).
--   · Puntajes en NUMERIC; todo puntaje comparable se normaliza a escala 0–100.
--   · Fechas en TIMESTAMPTZ (PostgreSQL las guarda en UTC internamente).
--   · Estados fijos: VARCHAR + CHECK (más fácil de evolucionar que CREATE TYPE ... ENUM).
--   · PK: id · FK: fk_<tabla>_<referida> · UNIQUE: uq_<tabla>_<campos>
--     índices: ix_<tabla>_<campos> · CHECK: ck_<tabla>_<regla>
--   · Los triggers usan RAISE EXCEPTION (SQLSTATE 23514 / P0001).
--   · Usuario que hace el cambio para el historial: SET LOCAL app.usuario_id = '<id>';
--     dentro de la transacción de la aplicación.
-- =====================================================================

CREATE SCHEMA IF NOT EXISTS talento;
SET search_path = talento, public;

-- Mantiene updated_at (equivale a ON UPDATE CURRENT_TIMESTAMP de MySQL)
CREATE FUNCTION set_updated_at() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  NEW.updated_at := now();
  RETURN NEW;
END $$;

-- =====================================================================
-- 1. SEGURIDAD, PERSONAS Y TRAZABILIDAD
-- =====================================================================

CREATE TABLE rol (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(30) NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_rol_codigo UNIQUE (codigo)
);
COMMENT ON TABLE rol IS 'Roles del sistema: administrador, ayudante, egresado, candidato externo, empresa. Un usuario tiene un solo rol';

CREATE TABLE usuario (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  rol_id INTEGER NOT NULL,
  correo VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE','ACTIVO','BLOQUEADO','INACTIVO')),
  intentos_fallidos SMALLINT NOT NULL DEFAULT 0,
  ultimo_acceso TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_usuario_correo UNIQUE (correo),
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES rol (id)
);
COMMENT ON TABLE usuario IS 'Credenciales de acceso. Un único login; cada usuario tiene exactamente un rol';
CREATE INDEX ix_usuario_rol ON usuario (rol_id);
CREATE TRIGGER trg_usuario_updated BEFORE UPDATE ON usuario FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE persona (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  usuario_id BIGINT NULL,
  nombres VARCHAR(100) NOT NULL,
  apellido_paterno VARCHAR(60) NULL,
  apellido_materno VARCHAR(60) NULL,
  ci_numero VARCHAR(15) NOT NULL,
  ci_complemento VARCHAR(3) NOT NULL DEFAULT '',
  ci_expedido VARCHAR(10) NULL CHECK (ci_expedido IN ('LP','CB','SC','OR','PT','CH','TJ','BE','PD')),
  fecha_nacimiento DATE NOT NULL,
  telefono VARCHAR(20) NULL,
  correo_contacto VARCHAR(190) NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_persona_usuario UNIQUE (usuario_id),
  CONSTRAINT uq_persona_ci UNIQUE (ci_numero, ci_complemento),
  CONSTRAINT fk_persona_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id),
  CONSTRAINT ck_persona_apellido CHECK (apellido_paterno IS NOT NULL OR apellido_materno IS NOT NULL)
);
COMMENT ON TABLE persona IS 'Identidad de toda persona (egresado, candidato externo, contacto, personal). usuario_id es NULL hasta que acepta la invitación';
CREATE INDEX ix_persona_apellidos ON persona (apellido_paterno, apellido_materno, nombres);
CREATE TRIGGER trg_persona_updated BEFORE UPDATE ON persona FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE consentimiento (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  persona_id BIGINT NOT NULL,
  tipo VARCHAR(26) NOT NULL CHECK (tipo IN ('TRATAMIENTO_DATOS','PUBLICACION_PERFIL','INVESTIGACION_ANTECEDENTES')),
  version_politica VARCHAR(20) NOT NULL,
  otorgado BOOLEAN NOT NULL,
  registrado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  ip_origen INET NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_consentimiento_persona FOREIGN KEY (persona_id) REFERENCES persona (id)
);
COMMENT ON TABLE consentimiento IS 'Registro histórico (solo inserciones) de consentimientos y revocaciones. El estado vigente es la última fila por persona y tipo';
CREATE INDEX ix_consentimiento_persona_tipo ON consentimiento (persona_id, tipo, registrado_en);

CREATE TABLE archivo (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  clave_almacenamiento VARCHAR(400) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  tamano_bytes BIGINT NOT NULL,
  hash_sha256 CHAR(64) NOT NULL,
  subido_por BIGINT NULL,
  creado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_archivo_clave UNIQUE (clave_almacenamiento),
  CONSTRAINT fk_archivo_subido_por FOREIGN KEY (subido_por) REFERENCES usuario (id) ON DELETE SET NULL,
  CONSTRAINT ck_archivo_tamano CHECK (tamano_bytes > 0)
);
COMMENT ON TABLE archivo IS 'Metadatos de archivos. El contenido vive en disco u object storage, nunca como BLOB';
CREATE INDEX ix_archivo_hash ON archivo (hash_sha256);

-- =====================================================================
-- 2. CATÁLOGO ACADÉMICO Y EGRESADOS
-- =====================================================================

CREATE TABLE facultad (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  sigla VARCHAR(15) NULL,
  ppa_minimo DECIMAL(5,2) NOT NULL DEFAULT 51.00,
  activa BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  CONSTRAINT uq_facultad_nombre UNIQUE (nombre),
  CONSTRAINT uq_facultad_sigla UNIQUE (sigla),
  CONSTRAINT ck_facultad_ppa CHECK (ppa_minimo BETWEEN 0 AND 100)
);
COMMENT ON TABLE facultad IS 'Facultades UAGRM. ppa_minimo lo define la administración y rige la evaluación técnica del flujo A';

CREATE TABLE carrera (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  facultad_id INTEGER NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  activa BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  CONSTRAINT uq_carrera_facultad_nombre UNIQUE (facultad_id, nombre),
  CONSTRAINT fk_carrera_facultad FOREIGN KEY (facultad_id) REFERENCES facultad (id)
);
COMMENT ON TABLE carrera IS 'Carreras de cada facultad';

CREATE TABLE area_preferencia (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  carrera_id INTEGER NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_area_carrera_nombre UNIQUE (carrera_id, nombre),
  CONSTRAINT uq_area_id_carrera UNIQUE (id, carrera_id),
  CONSTRAINT fk_area_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id)
);
COMMENT ON TABLE area_preferencia IS 'Áreas de preferencia por carrera (ej. Derecho: penal, civil, laboral)';

CREATE TABLE ayudante_carrera (
  usuario_id BIGINT NOT NULL,
  carrera_id INTEGER NOT NULL,
  PRIMARY KEY (usuario_id, carrera_id),
  CONSTRAINT fk_ayudante_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE,
  CONSTRAINT fk_ayudante_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id)
);
COMMENT ON TABLE ayudante_carrera IS 'Carreras que atiende cada junior/ayudante; define su bandeja de tareas manuales';
CREATE INDEX ix_ayudante_carrera_carrera ON ayudante_carrera (carrera_id);

CREATE TABLE egresado (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  persona_id BIGINT NOT NULL,
  carrera_id INTEGER NOT NULL,
  area_preferencia_id INTEGER NULL,
  registro_universitario VARCHAR(20) NOT NULL,
  anio_egreso SMALLINT NOT NULL,
  ppa DECIMAL(5,2) NOT NULL,
  titulo_verificado BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_egresado_registro UNIQUE (registro_universitario),
  CONSTRAINT uq_egresado_persona_carrera UNIQUE (persona_id, carrera_id),
  CONSTRAINT fk_egresado_persona FOREIGN KEY (persona_id) REFERENCES persona (id),
  CONSTRAINT fk_egresado_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id),
  -- El área de preferencia debe pertenecer a la misma carrera (FK compuesta; se omite si el área es NULL)
  CONSTRAINT fk_egresado_area FOREIGN KEY (area_preferencia_id, carrera_id) REFERENCES area_preferencia (id, carrera_id),
  CONSTRAINT ck_egresado_ppa  CHECK (ppa BETWEEN 0 AND 100),
  CONSTRAINT ck_egresado_anio CHECK (anio_egreso BETWEEN 1950 AND 2100)
);
COMMENT ON TABLE egresado IS 'Perfil académico por carrera de un egresado titulado. La facultad se deduce de la carrera';
CREATE INDEX ix_egresado_carrera ON egresado (carrera_id);
CREATE TRIGGER trg_egresado_updated BEFORE UPDATE ON egresado FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- =====================================================================
-- 3. EMPRESAS CLIENTE (FLUJO B)
-- =====================================================================

CREATE TABLE empresa (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  razon_social VARCHAR(200) NOT NULL,
  nit VARCHAR(20) NOT NULL,
  correo VARCHAR(190) NOT NULL,
  telefono VARCHAR(30) NULL,
  direccion VARCHAR(255) NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'ACTIVA' CHECK (estado IN ('ACTIVA','INACTIVA')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_empresa_nit UNIQUE (nit)
);
COMMENT ON TABLE empresa IS 'Empresas que contratan evaluaciones o consultan talento';
CREATE TRIGGER trg_empresa_updated BEFORE UPDATE ON empresa FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE contacto_empresa (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  empresa_id BIGINT NOT NULL,
  persona_id BIGINT NOT NULL,
  cargo VARCHAR(100) NOT NULL,
  es_principal BOOLEAN NOT NULL DEFAULT FALSE,
  activo BOOLEAN NOT NULL DEFAULT TRUE,
  -- Garantiza un solo contacto principal por empresa (UNIQUE sobre columna generada)
  PRIMARY KEY (id),
  CONSTRAINT uq_contacto_persona UNIQUE (persona_id),
  CONSTRAINT fk_contacto_empresa FOREIGN KEY (empresa_id) REFERENCES empresa (id),
  CONSTRAINT fk_contacto_persona FOREIGN KEY (persona_id) REFERENCES persona (id)
);
COMMENT ON TABLE contacto_empresa IS 'Usuarios de la empresa cliente (rol EMPRESA) y su cargo';
CREATE INDEX ix_contacto_empresa ON contacto_empresa (empresa_id);

-- =====================================================================
-- 4. CATÁLOGOS DE EVALUACIÓN Y BANCO DE PREGUNTAS
-- =====================================================================

CREATE TABLE etapa (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  ejecucion VARCHAR(10) NOT NULL CHECK (ejecucion IN ('AUTOMATICA','MANUAL')),
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_etapa_codigo UNIQUE (codigo)
);
COMMENT ON TABLE etapa IS 'Catálogo de tipos de etapa. MANUAL genera una tarea en la bandeja del ayudante';

CREATE TABLE dimension_evaluada (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  tipo VARCHAR(16) NOT NULL CHECK (tipo IN ('PERSONALIDAD','HABILIDAD_BLANDA','HABILIDAD_DURA')),
  orden SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT uq_dimension_codigo UNIQUE (codigo)
);
COMMENT ON TABLE dimension_evaluada IS 'Factores de personalidad y habilidades medibles; base del gráfico perfil ideal vs real';

CREATE TABLE prueba (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(50) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('TEST','RUBRICA','CALCULADA')),
  descripcion TEXT NULL,
  es_plantilla BOOLEAN NOT NULL DEFAULT TRUE,
  activa BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_prueba_codigo UNIQUE (codigo),
  CONSTRAINT uq_prueba_id_tipo UNIQUE (id, tipo)
);
COMMENT ON TABLE prueba IS 'Instrumentos puntuables reutilizables: TEST en línea, RUBRICA manual (CV, entrevista) o CALCULADA (PPA)';

CREATE TABLE rubrica_criterio (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  prueba_id INTEGER NOT NULL,
  tipo_prueba VARCHAR(10) NOT NULL DEFAULT 'RUBRICA' CHECK (tipo_prueba IN ('TEST','RUBRICA','CALCULADA')),
  nombre VARCHAR(100) NOT NULL,
  puntaje_maximo DECIMAL(5,2) NOT NULL,
  orden SMALLINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  CONSTRAINT uq_criterio_prueba_nombre UNIQUE (prueba_id, nombre),
  -- Discriminador: solo una prueba de tipo RUBRICA puede tener criterios
  CONSTRAINT fk_criterio_prueba FOREIGN KEY (prueba_id, tipo_prueba) REFERENCES prueba (id, tipo),
  CONSTRAINT ck_criterio_tipo   CHECK (tipo_prueba = 'RUBRICA'),
  CONSTRAINT ck_criterio_maximo CHECK (puntaje_maximo > 0)
);
COMMENT ON TABLE rubrica_criterio IS 'Criterios de una prueba tipo RUBRICA (ej. CV: formación, experiencia, habilidades, presentación)';

CREATE TABLE pregunta (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  prueba_id INTEGER NOT NULL,
  tipo_prueba VARCHAR(10) NOT NULL DEFAULT 'TEST' CHECK (tipo_prueba IN ('TEST','RUBRICA','CALCULADA')),
  dimension_id INTEGER NULL,
  orden SMALLINT NOT NULL,
  enunciado TEXT NULL,
  imagen_archivo_id BIGINT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_pregunta_prueba_orden UNIQUE (prueba_id, orden),
  -- Discriminador: solo una prueba de tipo TEST puede tener preguntas
  CONSTRAINT fk_pregunta_prueba    FOREIGN KEY (prueba_id, tipo_prueba) REFERENCES prueba (id, tipo),
  CONSTRAINT ck_pregunta_tipo      CHECK (tipo_prueba = 'TEST'),
  CONSTRAINT fk_pregunta_dimension FOREIGN KEY (dimension_id)      REFERENCES dimension_evaluada (id),
  CONSTRAINT fk_pregunta_imagen    FOREIGN KEY (imagen_archivo_id) REFERENCES archivo (id),
  CONSTRAINT ck_pregunta_contenido CHECK (enunciado IS NOT NULL OR imagen_archivo_id IS NOT NULL)
);
COMMENT ON TABLE pregunta IS 'Banco de preguntas de un TEST (texto y/o imagen de la figura)';
CREATE INDEX ix_pregunta_dimension ON pregunta (dimension_id);

CREATE TABLE opcion_pregunta (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  pregunta_id BIGINT NOT NULL,
  etiqueta CHAR(1) NOT NULL,
  texto VARCHAR(500) NULL,
  imagen_archivo_id BIGINT NULL,
  puntaje DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT uq_opcion_pregunta_etiqueta UNIQUE (pregunta_id, etiqueta),
  CONSTRAINT uq_opcion_id_pregunta UNIQUE (id, pregunta_id),
  CONSTRAINT fk_opcion_pregunta FOREIGN KEY (pregunta_id)       REFERENCES pregunta (id) ON DELETE CASCADE,
  CONSTRAINT fk_opcion_imagen   FOREIGN KEY (imagen_archivo_id) REFERENCES archivo (id),
  CONSTRAINT ck_opcion_puntaje  CHECK (puntaje >= 0),
  CONSTRAINT ck_opcion_contenido CHECK (texto IS NOT NULL OR imagen_archivo_id IS NOT NULL)
);
COMMENT ON TABLE opcion_pregunta IS 'Opciones A–E. Cada opción aporta un puntaje: sirve para pruebas con respuesta correcta y para personalidad';

-- =====================================================================
-- 5. PROCESOS, ETAPAS Y CONFIGURACIÓN
-- =====================================================================

CREATE TABLE proceso (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(30) NOT NULL,
  tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('TALENTO','EMPRESA')),
  nombre VARCHAR(200) NOT NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'BORRADOR' CHECK (estado IN ('BORRADOR','CONFIGURACION','ABIERTO','EN_EVALUACION','CERRADO','INFORME_EMITIDO','CANCELADO')),
  top_n SMALLINT NOT NULL DEFAULT 5,
  creado_por BIGINT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_proceso_codigo UNIQUE (codigo),
  CONSTRAINT uq_proceso_id_tipo UNIQUE (id, tipo),
  CONSTRAINT fk_proceso_creado_por FOREIGN KEY (creado_por) REFERENCES usuario (id),
  CONSTRAINT ck_proceso_top CHECK (top_n BETWEEN 1 AND 50)
);
COMMENT ON TABLE proceso IS 'Supertipo: motor común de evaluación para convocatorias de talento y solicitudes de empresa';
CREATE INDEX ix_proceso_tipo_estado ON proceso (tipo, estado);
CREATE TRIGGER trg_proceso_updated BEFORE UPDATE ON proceso FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE convocatoria_talento (
  proceso_id BIGINT NOT NULL,
  tipo_proceso VARCHAR(10) NOT NULL DEFAULT 'TALENTO' CHECK (tipo_proceso IN ('TALENTO','EMPRESA')),
  carrera_id INTEGER NOT NULL,
  gestion SMALLINT NOT NULL,
  numero SMALLINT NOT NULL DEFAULT 1,
  fecha_apertura DATE NOT NULL,
  fecha_cierre DATE NULL,
  vigencia_perfil_meses SMALLINT NOT NULL DEFAULT 12,
  PRIMARY KEY (proceso_id),
  CONSTRAINT uq_convocatoria_carrera_gestion UNIQUE (carrera_id, gestion, numero),
  CONSTRAINT fk_convocatoria_proceso FOREIGN KEY (proceso_id, tipo_proceso) REFERENCES proceso (id, tipo),
  CONSTRAINT fk_convocatoria_carrera FOREIGN KEY (carrera_id) REFERENCES carrera (id),
  CONSTRAINT ck_convocatoria_tipo  CHECK (tipo_proceso = 'TALENTO'),
  CONSTRAINT ck_convocatoria_fechas CHECK (fecha_cierre IS NULL OR fecha_cierre >= fecha_apertura)
);
COMMENT ON TABLE convocatoria_talento IS 'Subtipo flujo A: convocatoria por carrera y gestión. Su ranking alimenta la landing pública';

CREATE TABLE solicitud_empresa (
  proceso_id BIGINT NOT NULL,
  tipo_proceso VARCHAR(10) NOT NULL DEFAULT 'EMPRESA' CHECK (tipo_proceso IN ('TALENTO','EMPRESA')),
  contacto_id BIGINT NOT NULL,
  cargo VARCHAR(150) NOT NULL,
  area VARCHAR(100) NULL,
  nivel_jerarquico VARCHAR(10) NOT NULL CHECK (nivel_jerarquico IN ('OPERATIVO','TECNICO','JEFATURA','GERENCIA','DIRECCION')),
  vacantes SMALLINT NOT NULL DEFAULT 1,
  descripcion_funciones TEXT NOT NULL,
  enviada_en TIMESTAMPTZ NULL,
  PRIMARY KEY (proceso_id),
  CONSTRAINT fk_solicitud_proceso  FOREIGN KEY (proceso_id, tipo_proceso) REFERENCES proceso (id, tipo),
  CONSTRAINT fk_solicitud_contacto FOREIGN KEY (contacto_id) REFERENCES contacto_empresa (id),
  CONSTRAINT ck_solicitud_tipo     CHECK (tipo_proceso = 'EMPRESA'),
  CONSTRAINT ck_solicitud_vacantes CHECK (vacantes >= 1)
);
COMMENT ON TABLE solicitud_empresa IS 'Subtipo flujo B: la empresa se deduce del contacto solicitante (3FN)';
CREATE INDEX ix_solicitud_contacto ON solicitud_empresa (contacto_id);

CREATE TABLE proceso_etapa (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  proceso_id BIGINT NOT NULL,
  etapa_id INTEGER NOT NULL,
  orden SMALLINT NOT NULL,
  es_eliminatoria BOOLEAN NOT NULL DEFAULT TRUE,
  activa BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  CONSTRAINT uq_proceso_etapa_etapa UNIQUE (proceso_id, etapa_id),
  CONSTRAINT uq_proceso_etapa_orden UNIQUE (proceso_id, orden),
  CONSTRAINT fk_proceso_etapa_proceso FOREIGN KEY (proceso_id) REFERENCES proceso (id),
  CONSTRAINT fk_proceso_etapa_etapa   FOREIGN KEY (etapa_id)   REFERENCES etapa (id)
);
COMMENT ON TABLE proceso_etapa IS 'Etapas que componen un proceso, en orden, y si eliminan al candidato que no las supera';
CREATE INDEX ix_proceso_etapa_etapa ON proceso_etapa (etapa_id);

CREATE TABLE proceso_prueba (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  proceso_etapa_id BIGINT NOT NULL,
  prueba_id INTEGER NOT NULL,
  orden SMALLINT NOT NULL DEFAULT 1,
  peso DECIMAL(5,2) NOT NULL DEFAULT 0,
  obligatoria BOOLEAN NOT NULL DEFAULT TRUE,
  puntaje_minimo DECIMAL(5,2) NULL,
  umbral_origen VARCHAR(10) NOT NULL DEFAULT 'FIJO' CHECK (umbral_origen IN ('FIJO','FACULTAD')),
  tiempo_minimo_seg INTEGER NULL,
  tiempo_maximo_seg INTEGER NULL,
  max_intentos SMALLINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  CONSTRAINT uq_proceso_prueba UNIQUE (proceso_etapa_id, prueba_id),
  CONSTRAINT fk_proceso_prueba_etapa  FOREIGN KEY (proceso_etapa_id) REFERENCES proceso_etapa (id),
  CONSTRAINT fk_proceso_prueba_prueba FOREIGN KEY (prueba_id)        REFERENCES prueba (id),
  CONSTRAINT ck_proceso_prueba_peso     CHECK (peso BETWEEN 0 AND 100),
  CONSTRAINT ck_proceso_prueba_minimo   CHECK (puntaje_minimo IS NULL OR puntaje_minimo BETWEEN 0 AND 100),
  CONSTRAINT ck_proceso_prueba_intentos CHECK (max_intentos >= 1),
  CONSTRAINT ck_proceso_prueba_tiempos  CHECK (tiempo_maximo_seg IS NULL OR tiempo_minimo_seg IS NULL OR tiempo_maximo_seg >= tiempo_minimo_seg)
);
COMMENT ON TABLE proceso_prueba IS 'Configuración de cada prueba dentro de un proceso: peso, nota mínima, tiempos e intentos. umbral_origen=FACULTAD usa facultad.ppa_minimo';
CREATE INDEX ix_proceso_prueba_prueba ON proceso_prueba (prueba_id);

CREATE TABLE perfil_requerido (
  proceso_id BIGINT NOT NULL,
  dimension_id INTEGER NOT NULL,
  valor_ideal SMALLINT NOT NULL,
  PRIMARY KEY (proceso_id, dimension_id),
  CONSTRAINT fk_perfil_proceso   FOREIGN KEY (proceso_id)   REFERENCES proceso (id) ON DELETE CASCADE,
  CONSTRAINT fk_perfil_dimension FOREIGN KEY (dimension_id) REFERENCES dimension_evaluada (id),
  CONSTRAINT ck_perfil_valor CHECK (valor_ideal BETWEEN 1 AND 10)
);
COMMENT ON TABLE perfil_requerido IS 'Perfil psicológico y de habilidades ideal de un proceso (valor 1–10 por dimensión)';
CREATE INDEX ix_perfil_dimension ON perfil_requerido (dimension_id);

CREATE TABLE tipo_documento (
  id INTEGER GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_tipo_documento_codigo UNIQUE (codigo)
);
COMMENT ON TABLE tipo_documento IS 'Catálogo de documentos solicitables (histórico, cédula, foto, CV)';

CREATE TABLE requisito_documento (
  proceso_id BIGINT NOT NULL,
  tipo_documento_id INTEGER NOT NULL,
  obligatorio BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (proceso_id, tipo_documento_id),
  CONSTRAINT fk_requisito_proceso FOREIGN KEY (proceso_id)        REFERENCES proceso (id) ON DELETE CASCADE,
  CONSTRAINT fk_requisito_tipo    FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento (id)
);
COMMENT ON TABLE requisito_documento IS 'Documentos que cada proceso exige a sus postulantes';

-- =====================================================================
-- 6. POSTULACIONES Y EJECUCIÓN DEL PROCESO
-- =====================================================================

CREATE TABLE postulacion (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo VARCHAR(20) NOT NULL,
  proceso_id BIGINT NOT NULL,
  persona_id BIGINT NOT NULL,
  registrada_por BIGINT NULL,
  estado VARCHAR(11) NOT NULL DEFAULT 'INSCRITA' CHECK (estado IN ('INSCRITA','EN_PROCESO','APROBADA','NO_APROBADA','RETIRADA')),
  motivo_cierre VARCHAR(500) NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_postulacion_codigo UNIQUE (codigo),
  CONSTRAINT uq_postulacion_proceso_persona UNIQUE (proceso_id, persona_id),
  CONSTRAINT fk_postulacion_proceso        FOREIGN KEY (proceso_id)     REFERENCES proceso (id),
  CONSTRAINT fk_postulacion_persona        FOREIGN KEY (persona_id)     REFERENCES persona (id),
  CONSTRAINT fk_postulacion_registrada_por FOREIGN KEY (registrada_por) REFERENCES contacto_empresa (id)
);
COMMENT ON TABLE postulacion IS 'Candidato dentro de un proceso. codigo es el identificador seudónimo que se muestra en la landing pública';
CREATE INDEX ix_postulacion_persona ON postulacion (persona_id);
CREATE INDEX ix_postulacion_proceso_estado ON postulacion (proceso_id, estado);
CREATE INDEX ix_postulacion_registrada_por ON postulacion (registrada_por);
CREATE TRIGGER trg_postulacion_updated BEFORE UPDATE ON postulacion FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE documento_postulacion (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  tipo_documento_id INTEGER NOT NULL,
  archivo_id BIGINT NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE','VALIDADO','OBSERVADO','RECHAZADO')),
  es_vigente BOOLEAN NOT NULL DEFAULT TRUE,
  validado_por BIGINT NULL,
  validado_en TIMESTAMPTZ NULL,
  observacion VARCHAR(500) NULL,
  creado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  -- Un solo documento vigente por postulación y tipo; las versiones anteriores quedan como historial
  PRIMARY KEY (id),
  CONSTRAINT uq_documento_archivo UNIQUE (archivo_id),
  CONSTRAINT fk_documento_postulacion FOREIGN KEY (postulacion_id)    REFERENCES postulacion (id),
  CONSTRAINT fk_documento_tipo        FOREIGN KEY (tipo_documento_id) REFERENCES tipo_documento (id),
  CONSTRAINT fk_documento_archivo     FOREIGN KEY (archivo_id)        REFERENCES archivo (id),
  CONSTRAINT fk_documento_validado    FOREIGN KEY (validado_por)      REFERENCES usuario (id)
);
COMMENT ON TABLE documento_postulacion IS 'Documentos subidos por el postulante y su estado de validación';
CREATE INDEX ix_documento_postulacion ON documento_postulacion (postulacion_id);
CREATE INDEX ix_documento_tipo ON documento_postulacion (tipo_documento_id);

CREATE TABLE postulacion_etapa (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  proceso_etapa_id BIGINT NOT NULL,
  estado VARCHAR(11) NOT NULL DEFAULT 'BLOQUEADA' CHECK (estado IN ('BLOQUEADA','PENDIENTE','EN_CURSO','EN_REVISION','APROBADA','REPROBADA','OMITIDA')),
  asignado_a BIGINT NULL,
  asignado_en TIMESTAMPTZ NULL,
  iniciada_en TIMESTAMPTZ NULL,
  finalizada_en TIMESTAMPTZ NULL,
  motivo VARCHAR(500) NULL,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_postulacion_etapa UNIQUE (postulacion_id, proceso_etapa_id),
  CONSTRAINT fk_postulacion_etapa_postulacion FOREIGN KEY (postulacion_id)   REFERENCES postulacion (id),
  CONSTRAINT fk_postulacion_etapa_proceso     FOREIGN KEY (proceso_etapa_id) REFERENCES proceso_etapa (id),
  CONSTRAINT fk_postulacion_etapa_asignado    FOREIGN KEY (asignado_a)       REFERENCES usuario (id),
  CONSTRAINT ck_postulacion_etapa_fechas CHECK (finalizada_en IS NULL OR iniciada_en IS NULL OR finalizada_en >= iniciada_en)
);
COMMENT ON TABLE postulacion_etapa IS 'Máquina de estados: avance del candidato por cada etapa. Las filas MANUAL en revisión forman la bandeja de tareas';
CREATE INDEX ix_postulacion_etapa_bandeja ON postulacion_etapa (asignado_a, estado);
CREATE INDEX ix_postulacion_etapa_embudo ON postulacion_etapa (proceso_etapa_id, estado);
CREATE TRIGGER trg_postulacion_etapa_updated BEFORE UPDATE ON postulacion_etapa FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE historial_postulacion_etapa (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_etapa_id BIGINT NOT NULL,
  estado_anterior VARCHAR(11) NULL CHECK (estado_anterior IN ('BLOQUEADA','PENDIENTE','EN_CURSO','EN_REVISION','APROBADA','REPROBADA','OMITIDA')),
  estado_nuevo VARCHAR(11) NOT NULL CHECK (estado_nuevo IN ('BLOQUEADA','PENDIENTE','EN_CURSO','EN_REVISION','APROBADA','REPROBADA','OMITIDA')),
  usuario_id BIGINT NULL,
  motivo VARCHAR(500) NULL,
  creado_en TIMESTAMPTZ(3) NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT fk_historial_postulacion_etapa FOREIGN KEY (postulacion_etapa_id) REFERENCES postulacion_etapa (id),
  CONSTRAINT fk_historial_usuario           FOREIGN KEY (usuario_id)           REFERENCES usuario (id) ON DELETE SET NULL
);
COMMENT ON TABLE historial_postulacion_etapa IS 'Quién cambió el estado de cada etapa y cuándo (lo alimentan los triggers de postulacion_etapa)';
CREATE INDEX ix_historial_etapa ON historial_postulacion_etapa (postulacion_etapa_id, creado_en);

CREATE TABLE invitacion_postulacion (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'ENVIADA' CHECK (estado IN ('ENVIADA','ACEPTADA','EXPIRADA','REVOCADA')),
  enviada_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  expira_en TIMESTAMPTZ NOT NULL,
  aceptada_en TIMESTAMPTZ NULL,
  reenvios SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT uq_invitacion_postulacion UNIQUE (postulacion_id),
  CONSTRAINT uq_invitacion_token UNIQUE (token_hash),
  CONSTRAINT fk_invitacion_postulacion FOREIGN KEY (postulacion_id) REFERENCES postulacion (id),
  CONSTRAINT ck_invitacion_vigencia CHECK (expira_en > enviada_en)
);
COMMENT ON TABLE invitacion_postulacion IS 'Invitación por correo al candidato externo de una empresa. Se guarda solo el hash del token';

CREATE TABLE intento_prueba (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  proceso_prueba_id BIGINT NOT NULL,
  nro_intento SMALLINT NOT NULL DEFAULT 1,
  estado VARCHAR(10) NOT NULL DEFAULT 'EN_CURSO' CHECK (estado IN ('EN_CURSO','FINALIZADO','EXPIRADO','ANULADO')),
  iniciado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  finalizado_en TIMESTAMPTZ NULL,
  duracion_seg INTEGER GENERATED ALWAYS AS (EXTRACT(EPOCH FROM (finalizado_en - iniciado_en))::integer) STORED,
  PRIMARY KEY (id),
  CONSTRAINT uq_intento UNIQUE (postulacion_id, proceso_prueba_id, nro_intento),
  CONSTRAINT uq_intento_id_post_prueba UNIQUE (id, postulacion_id, proceso_prueba_id),
  CONSTRAINT fk_intento_postulacion    FOREIGN KEY (postulacion_id)     REFERENCES postulacion (id),
  CONSTRAINT fk_intento_proceso_prueba FOREIGN KEY (proceso_prueba_id)  REFERENCES proceso_prueba (id),
  CONSTRAINT ck_intento_fechas CHECK (finalizado_en IS NULL OR finalizado_en >= iniciado_en)
);
COMMENT ON TABLE intento_prueba IS 'Cada vez que un candidato rinde un test en línea; duracion_seg permite aplicar tiempo mínimo y máximo';
CREATE INDEX ix_intento_proceso_prueba ON intento_prueba (proceso_prueba_id);

CREATE TABLE respuesta_intento (
  intento_id BIGINT NOT NULL,
  pregunta_id BIGINT NOT NULL,
  opcion_id BIGINT NULL,
  respondida_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (intento_id, pregunta_id),
  CONSTRAINT fk_respuesta_intento  FOREIGN KEY (intento_id)  REFERENCES intento_prueba (id) ON DELETE CASCADE,
  CONSTRAINT fk_respuesta_pregunta FOREIGN KEY (pregunta_id) REFERENCES pregunta (id),
  -- La opción elegida debe pertenecer a la pregunta respondida (se omite si opcion_id es NULL = sin responder)
  CONSTRAINT fk_respuesta_opcion   FOREIGN KEY (opcion_id, pregunta_id) REFERENCES opcion_pregunta (id, pregunta_id)
);
COMMENT ON TABLE respuesta_intento IS 'Respuestas de un intento. El puntaje se calcula uniendo con opcion_pregunta (no se duplica)';
CREATE INDEX ix_respuesta_pregunta ON respuesta_intento (pregunta_id);
CREATE INDEX ix_respuesta_opcion ON respuesta_intento (opcion_id, pregunta_id);

CREATE TABLE resultado_prueba (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  proceso_prueba_id BIGINT NOT NULL,
  intento_id BIGINT NULL,
  puntaje_bruto DECIMAL(7,2) NOT NULL,
  puntaje_maximo DECIMAL(7,2) NOT NULL,
  -- NULLIF es obligatorio: con triggers BEFORE INSERT, MySQL evalúa la columna generada sobre una fila
  -- aún vacía (ceros) y sin él falla con "Division by 0" aunque los datos reales sean válidos.
  puntaje_normalizado NUMERIC(5,2) GENERATED ALWAYS AS (ROUND(puntaje_bruto / puntaje_maximo * 100, 2)) STORED,
  umbral_aplicado DECIMAL(5,2) NULL,
  aprobada BOOLEAN NOT NULL,
  motivo_no_aprobacion VARCHAR(14) NULL CHECK (motivo_no_aprobacion IN ('PUNTAJE_BAJO','TIEMPO_MINIMO','TIEMPO_AGOTADO','OTRO')),
  evaluador_id BIGINT NULL,
  observaciones TEXT NULL,
  calificado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_resultado_postulacion_prueba UNIQUE (postulacion_id, proceso_prueba_id),
  CONSTRAINT uq_resultado_id_post_prueba UNIQUE (id, postulacion_id, proceso_prueba_id),
  CONSTRAINT fk_resultado_postulacion    FOREIGN KEY (postulacion_id)    REFERENCES postulacion (id),
  CONSTRAINT fk_resultado_proceso_prueba FOREIGN KEY (proceso_prueba_id) REFERENCES proceso_prueba (id),
  CONSTRAINT fk_resultado_evaluador      FOREIGN KEY (evaluador_id)      REFERENCES usuario (id),
  -- El intento contado debe ser de la misma postulación y la misma prueba (FK compuesta)
  CONSTRAINT fk_resultado_intento FOREIGN KEY (intento_id, postulacion_id, proceso_prueba_id) REFERENCES intento_prueba (id, postulacion_id, proceso_prueba_id),
  CONSTRAINT ck_resultado_maximo CHECK (puntaje_maximo > 0),
  CONSTRAINT ck_resultado_bruto  CHECK (puntaje_bruto >= 0 AND puntaje_bruto <= puntaje_maximo),
  CONSTRAINT ck_resultado_motivo CHECK (aprobada OR motivo_no_aprobacion IS NULL)
);
COMMENT ON TABLE resultado_prueba IS 'Resultado final de una prueba para una postulación (test, rúbrica o calculada). Guarda el umbral con el que se juzgó';
CREATE INDEX ix_resultado_proceso_prueba ON resultado_prueba (proceso_prueba_id);
CREATE INDEX ix_resultado_evaluador ON resultado_prueba (evaluador_id);

CREATE TABLE calificacion_criterio (
  resultado_prueba_id BIGINT NOT NULL,
  criterio_id INTEGER NOT NULL,
  puntaje DECIMAL(5,2) NOT NULL,
  comentario VARCHAR(500) NULL,
  PRIMARY KEY (resultado_prueba_id, criterio_id),
  CONSTRAINT fk_calificacion_resultado FOREIGN KEY (resultado_prueba_id) REFERENCES resultado_prueba (id) ON DELETE CASCADE,
  CONSTRAINT fk_calificacion_criterio  FOREIGN KEY (criterio_id)         REFERENCES rubrica_criterio (id),
  CONSTRAINT ck_calificacion_puntaje CHECK (puntaje >= 0)
);
COMMENT ON TABLE calificacion_criterio IS 'Puntaje por criterio en revisiones manuales (CV, entrevista)';
CREATE INDEX ix_calificacion_criterio ON calificacion_criterio (criterio_id);

CREATE TABLE resultado_dimension (
  resultado_prueba_id BIGINT NOT NULL,
  dimension_id INTEGER NOT NULL,
  valor DECIMAL(4,2) NOT NULL,
  PRIMARY KEY (resultado_prueba_id, dimension_id),
  CONSTRAINT fk_resultado_dimension_resultado FOREIGN KEY (resultado_prueba_id) REFERENCES resultado_prueba (id) ON DELETE CASCADE,
  CONSTRAINT fk_resultado_dimension_dimension FOREIGN KEY (dimension_id)        REFERENCES dimension_evaluada (id),
  CONSTRAINT ck_resultado_dimension_valor CHECK (valor BETWEEN 0 AND 10)
);
COMMENT ON TABLE resultado_dimension IS 'Valor real del candidato por dimensión (0–10); se compara con perfil_requerido para graficar ideal vs real';
CREATE INDEX ix_resultado_dimension ON resultado_dimension (dimension_id);

-- =====================================================================
-- 7. ANTECEDENTES LABORALES (FLUJO B, ETAPA 6)
-- =====================================================================

CREATE TABLE antecedente_laboral (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  postulacion_id BIGINT NOT NULL,
  empresa_nombre VARCHAR(200) NOT NULL,
  cargo VARCHAR(150) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NULL,
  referente_nombre VARCHAR(150) NULL,
  referente_telefono VARCHAR(30) NULL,
  estado_verificacion VARCHAR(13) NOT NULL DEFAULT 'NO_CONTACTADO' CHECK (estado_verificacion IN ('NO_CONTACTADO','CONTACTADO','VERIFICADO','INCONSISTENTE')),
  fecha_contacto DATE NULL,
  persona_contactada VARCHAR(150) NULL,
  calificacion_desempeno SMALLINT NULL,
  es_puntual BOOLEAN NULL,
  es_responsable BOOLEAN NULL,
  trabaja_en_equipo BOOLEAN NULL,
  motivo_salida VARCHAR(300) NULL,
  lo_recontrataria BOOLEAN NULL,
  observaciones TEXT NULL,
  verificado_por BIGINT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_antecedente_postulacion FOREIGN KEY (postulacion_id) REFERENCES postulacion (id),
  CONSTRAINT fk_antecedente_verificador FOREIGN KEY (verificado_por) REFERENCES usuario (id),
  CONSTRAINT ck_antecedente_fechas      CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
  CONSTRAINT ck_antecedente_calificacion CHECK (calificacion_desempeno IS NULL OR calificacion_desempeno BETWEEN 1 AND 5)
);
COMMENT ON TABLE antecedente_laboral IS 'Cada empleo declarado por el candidato y el resultado de verificarlo con la referencia';
CREATE INDEX ix_antecedente_postulacion ON antecedente_laboral (postulacion_id);
CREATE INDEX ix_antecedente_verificador ON antecedente_laboral (verificado_por);

CREATE TABLE investigacion_antecedentes (
  postulacion_id BIGINT NOT NULL,
  semaforo VARCHAR(17) NOT NULL CHECK (semaforo IN ('SIN_OBSERVACIONES','CON_OBSERVACIONES','ALERTA')),
  conclusion TEXT NULL,
  investigador_id BIGINT NULL,
  completada_en TIMESTAMPTZ NULL,
  PRIMARY KEY (postulacion_id),
  CONSTRAINT fk_investigacion_postulacion  FOREIGN KEY (postulacion_id)  REFERENCES postulacion (id),
  CONSTRAINT fk_investigacion_investigador FOREIGN KEY (investigador_id) REFERENCES usuario (id)
);
COMMENT ON TABLE investigacion_antecedentes IS 'Conclusión global de la investigación de antecedentes de un candidato (semáforo)';
CREATE INDEX ix_investigacion_investigador ON investigacion_antecedentes (investigador_id);

-- =====================================================================
-- 8. SALIDAS: RANKING, INFORMES Y SOLICITUDES DE INFORMACIÓN
-- =====================================================================

CREATE TABLE ranking_resultado (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  proceso_id BIGINT NOT NULL,
  postulacion_id BIGINT NOT NULL,
  posicion INTEGER NOT NULL,
  puntaje_total DECIMAL(5,2) NOT NULL,
  es_top BOOLEAN NOT NULL DEFAULT FALSE,
  visible BOOLEAN NOT NULL DEFAULT FALSE,
  publicado_en TIMESTAMPTZ NULL,
  publicado_por BIGINT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_ranking_postulacion UNIQUE (proceso_id, postulacion_id),
  CONSTRAINT uq_ranking_posicion UNIQUE (proceso_id, posicion),
  CONSTRAINT fk_ranking_proceso      FOREIGN KEY (proceso_id)     REFERENCES proceso (id),
  CONSTRAINT fk_ranking_postulacion  FOREIGN KEY (postulacion_id) REFERENCES postulacion (id),
  CONSTRAINT fk_ranking_publicado_por FOREIGN KEY (publicado_por) REFERENCES usuario (id),
  CONSTRAINT ck_ranking_puntaje CHECK (puntaje_total BETWEEN 0 AND 100)
);
COMMENT ON TABLE ranking_resultado IS 'Ranking congelado al cierre del proceso. es_top marca el top-N; visible controla la publicación en la landing';
CREATE INDEX ix_ranking_postulacion ON ranking_resultado (postulacion_id);
CREATE INDEX ix_ranking_publico ON ranking_resultado (visible, publicado_en);

CREATE TABLE informe_final (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  proceso_id BIGINT NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'BORRADOR' CHECK (estado IN ('BORRADOR','EMITIDO')),
  resumen TEXT NULL,
  recomendacion_general TEXT NULL,
  archivo_pdf_id BIGINT NULL,
  elaborado_por BIGINT NOT NULL,
  emitido_en TIMESTAMPTZ NULL,
  PRIMARY KEY (id),
  CONSTRAINT uq_informe_proceso UNIQUE (proceso_id),
  CONSTRAINT fk_informe_proceso   FOREIGN KEY (proceso_id)     REFERENCES proceso (id),
  CONSTRAINT fk_informe_pdf       FOREIGN KEY (archivo_pdf_id) REFERENCES archivo (id),
  CONSTRAINT fk_informe_elaborado FOREIGN KEY (elaborado_por)  REFERENCES usuario (id),
  CONSTRAINT ck_informe_emision CHECK (estado = 'BORRADOR' OR emitido_en IS NOT NULL)
);
COMMENT ON TABLE informe_final IS 'Informe y recomendaciones que recibe la empresa al terminar su evaluación';
CREATE INDEX ix_informe_elaborado_por ON informe_final (elaborado_por);
CREATE INDEX ix_informe_pdf ON informe_final (archivo_pdf_id);

CREATE TABLE informe_candidato (
  informe_id BIGINT NOT NULL,
  postulacion_id BIGINT NOT NULL,
  etiqueta VARCHAR(24) NOT NULL CHECK (etiqueta IN ('ALTAMENTE_RECOMENDADO','RECOMENDADO','RECOMENDADO_CON_RESERVAS','NO_RECOMENDADO')),
  recomendacion TEXT NULL,
  PRIMARY KEY (informe_id, postulacion_id),
  CONSTRAINT fk_informe_candidato_informe     FOREIGN KEY (informe_id)     REFERENCES informe_final (id) ON DELETE CASCADE,
  CONSTRAINT fk_informe_candidato_postulacion FOREIGN KEY (postulacion_id) REFERENCES postulacion (id)
);
COMMENT ON TABLE informe_candidato IS 'Dictamen de la consultora por candidato dentro del informe';
CREATE INDEX ix_informe_candidato_postulacion ON informe_candidato (postulacion_id);

CREATE TABLE solicitud_informacion (
  id BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
  codigo_seguimiento VARCHAR(20) NOT NULL,
  ranking_resultado_id BIGINT NOT NULL,
  empresa_nombre VARCHAR(200) NOT NULL,
  nit VARCHAR(20) NOT NULL,
  contacto_nombre VARCHAR(150) NOT NULL,
  contacto_cargo VARCHAR(100) NOT NULL,
  contacto_correo VARCHAR(190) NOT NULL,
  contacto_telefono VARCHAR(30) NULL,
  vacante VARCHAR(200) NOT NULL,
  mensaje TEXT NULL,
  acepta_politica BOOLEAN NOT NULL,
  estado VARCHAR(10) NOT NULL DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE','APROBADA','RECHAZADA','ENTREGADA')),
  motivo_rechazo VARCHAR(500) NULL,
  resuelto_por BIGINT NULL,
  resuelto_en TIMESTAMPTZ NULL,
  entregada_en TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (id),
  CONSTRAINT uq_solicitud_info_codigo UNIQUE (codigo_seguimiento),
  CONSTRAINT fk_solicitud_info_ranking  FOREIGN KEY (ranking_resultado_id) REFERENCES ranking_resultado (id),
  CONSTRAINT fk_solicitud_info_resuelto FOREIGN KEY (resuelto_por)         REFERENCES usuario (id),
  CONSTRAINT ck_solicitud_info_politica CHECK (acepta_politica),
  CONSTRAINT ck_solicitud_info_rechazo  CHECK (estado <> 'RECHAZADA' OR motivo_rechazo IS NOT NULL)
);
COMMENT ON TABLE solicitud_informacion IS 'Pedido público de una empresa para obtener datos de contacto de un perfil. La consultora aprueba y entrega';
CREATE INDEX ix_solicitud_info_estado ON solicitud_informacion (estado, created_at);
CREATE INDEX ix_solicitud_info_ranking ON solicitud_informacion (ranking_resultado_id);
CREATE INDEX ix_solicitud_info_resuelto ON solicitud_informacion (resuelto_por);

CREATE TABLE parametro_sistema (
  clave VARCHAR(80) NOT NULL,
  valor VARCHAR(500) NOT NULL,
  descripcion VARCHAR(255) NULL,
  actualizado_por BIGINT NULL,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (clave),
  CONSTRAINT fk_parametro_usuario FOREIGN KEY (actualizado_por) REFERENCES usuario (id) ON DELETE SET NULL
);
COMMENT ON TABLE parametro_sistema IS 'Parámetros globales editables por el administrador';
CREATE TRIGGER trg_parametro_sistema_updated BEFORE UPDATE ON parametro_sistema FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- =====================================================================
-- 8b. ÍNDICES ÚNICOS PARCIALES (reemplazan las columnas generadas de MySQL)
-- =====================================================================

-- Un solo contacto principal por empresa
CREATE UNIQUE INDEX uq_contacto_principal ON contacto_empresa (empresa_id) WHERE es_principal;

-- Un solo documento vigente por postulación y tipo; las versiones anteriores quedan como historial
CREATE UNIQUE INDEX uq_documento_vigente ON documento_postulacion (postulacion_id, tipo_documento_id) WHERE es_vigente;

-- =====================================================================
-- 9. TRIGGERS (reglas entre tablas que una FK no puede expresar)
-- =====================================================================

-- Una postulación debe ser coherente con el tipo de proceso
CREATE FUNCTION fn_postulacion_bi() RETURNS trigger LANGUAGE plpgsql AS $$
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
CREATE TRIGGER trg_postulacion_bi BEFORE INSERT ON postulacion
  FOR EACH ROW EXECUTE FUNCTION fn_postulacion_bi();

-- La etapa debe pertenecer al mismo proceso que la postulación
CREATE FUNCTION fn_postulacion_etapa_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
     IS DISTINCT FROM (SELECT proceso_id FROM proceso_etapa WHERE id = NEW.proceso_etapa_id) THEN
    RAISE EXCEPTION 'La etapa no pertenece al proceso de la postulación';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_postulacion_etapa_bi BEFORE INSERT ON postulacion_etapa
  FOR EACH ROW EXECUTE FUNCTION fn_postulacion_etapa_bi();

-- Historial de estados de etapa (el usuario se toma de la variable de sesión app.usuario_id)
CREATE FUNCTION fn_postulacion_etapa_hist() RETURNS trigger LANGUAGE plpgsql AS $$
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
CREATE TRIGGER trg_postulacion_etapa_ai AFTER INSERT ON postulacion_etapa
  FOR EACH ROW EXECUTE FUNCTION fn_postulacion_etapa_hist();
CREATE TRIGGER trg_postulacion_etapa_au AFTER UPDATE ON postulacion_etapa
  FOR EACH ROW EXECUTE FUNCTION fn_postulacion_etapa_hist();

-- El intento debe ser de una prueba TEST del mismo proceso y no superar los intentos permitidos
CREATE FUNCTION fn_intento_prueba_bi() RETURNS trigger LANGUAGE plpgsql AS $$
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
CREATE TRIGGER trg_intento_prueba_bi BEFORE INSERT ON intento_prueba
  FOR EACH ROW EXECUTE FUNCTION fn_intento_prueba_bi();

-- La pregunta respondida debe ser del test que se está rindiendo
CREATE FUNCTION fn_respuesta_intento_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT prueba_id FROM pregunta WHERE id = NEW.pregunta_id)
     IS DISTINCT FROM (SELECT pp.prueba_id FROM intento_prueba i JOIN proceso_prueba pp ON pp.id = i.proceso_prueba_id
                        WHERE i.id = NEW.intento_id) THEN
    RAISE EXCEPTION 'La pregunta no pertenece a la prueba del intento';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_respuesta_intento_bi BEFORE INSERT ON respuesta_intento
  FOR EACH ROW EXECUTE FUNCTION fn_respuesta_intento_bi();

-- El resultado debe corresponder a una prueba del mismo proceso que la postulación
CREATE FUNCTION fn_resultado_prueba_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
     IS DISTINCT FROM (SELECT pe.proceso_id FROM proceso_prueba pp JOIN proceso_etapa pe ON pe.id = pp.proceso_etapa_id
                        WHERE pp.id = NEW.proceso_prueba_id) THEN
    RAISE EXCEPTION 'La prueba no pertenece al proceso de la postulación';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_resultado_prueba_bi BEFORE INSERT ON resultado_prueba
  FOR EACH ROW EXECUTE FUNCTION fn_resultado_prueba_bi();

-- El criterio calificado debe ser de la rúbrica de la prueba evaluada
CREATE FUNCTION fn_calificacion_criterio_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT prueba_id FROM rubrica_criterio WHERE id = NEW.criterio_id)
     IS DISTINCT FROM (SELECT pp.prueba_id FROM resultado_prueba r JOIN proceso_prueba pp ON pp.id = r.proceso_prueba_id
                        WHERE r.id = NEW.resultado_prueba_id) THEN
    RAISE EXCEPTION 'El criterio no pertenece a la rúbrica de la prueba';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_calificacion_criterio_bi BEFORE INSERT ON calificacion_criterio
  FOR EACH ROW EXECUTE FUNCTION fn_calificacion_criterio_bi();

-- El ranking solo puede incluir postulaciones del mismo proceso
CREATE FUNCTION fn_ranking_resultado_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id) IS DISTINCT FROM NEW.proceso_id THEN
    RAISE EXCEPTION 'La postulación no pertenece al proceso del ranking';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_ranking_resultado_bi BEFORE INSERT ON ranking_resultado
  FOR EACH ROW EXECUTE FUNCTION fn_ranking_resultado_bi();

-- El informe solo puede dictaminar candidatos de su propio proceso
CREATE FUNCTION fn_informe_candidato_bi() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  IF (SELECT proceso_id FROM postulacion WHERE id = NEW.postulacion_id)
     IS DISTINCT FROM (SELECT proceso_id FROM informe_final WHERE id = NEW.informe_id) THEN
    RAISE EXCEPTION 'La postulación no pertenece al proceso del informe';
  END IF;
  RETURN NEW;
END $$;
CREATE TRIGGER trg_informe_candidato_bi BEFORE INSERT ON informe_candidato
  FOR EACH ROW EXECUTE FUNCTION fn_informe_candidato_bi();

-- =====================================================================
-- 10. VISTAS
-- =====================================================================

-- Suma de pesos por proceso; debe ser 100 antes de abrirlo
CREATE VIEW v_proceso_pesos AS
SELECT pe.proceso_id,
       ROUND(COALESCE(SUM(pp.peso), 0), 2)            AS suma_pesos,
       (ROUND(COALESCE(SUM(pp.peso), 0), 2) = 100.00) AS pesos_validos
  FROM proceso_etapa pe
  LEFT JOIN proceso_prueba pp ON pp.proceso_etapa_id = pe.id
 WHERE pe.activa
 GROUP BY pe.proceso_id;

-- Puntaje total ponderado por postulación (asume pesos que suman 100)
CREATE VIEW v_puntaje_postulacion AS
SELECT po.id AS postulacion_id,
       po.proceso_id,
       ROUND(SUM(r.puntaje_normalizado * pp.peso) / 100, 2) AS puntaje_total,
       COUNT(r.id)                                          AS pruebas_calificadas,
       (SELECT COUNT(*)
          FROM proceso_prueba pp2
          JOIN proceso_etapa pe2 ON pe2.id = pp2.proceso_etapa_id
         WHERE pe2.proceso_id = po.proceso_id AND pe2.activa) AS pruebas_configuradas
  FROM postulacion po
  JOIN resultado_prueba r ON r.postulacion_id = po.id
  JOIN proceso_prueba pp  ON pp.id = r.proceso_prueba_id
 GROUP BY po.id, po.proceso_id;

-- Ranking provisional (antes de publicarlo): RANK() permite ver empates
CREATE VIEW v_ranking_provisional AS
SELECT vp.proceso_id,
       vp.postulacion_id,
       vp.puntaje_total,
       RANK() OVER (PARTITION BY vp.proceso_id ORDER BY vp.puntaje_total DESC) AS posicion_calculada
  FROM v_puntaje_postulacion vp;

-- Bandeja de tareas manuales (análisis de CV, entrevista, antecedentes)
CREATE VIEW v_bandeja_tareas AS
SELECT pt.id           AS tarea_id,
       pt.asignado_a,
       pt.estado,
       po.codigo       AS codigo_candidato,
       pr.codigo       AS codigo_proceso,
       pr.tipo         AS tipo_proceso,
       e.codigo        AS etapa,
       pt.asignado_en,
       FLOOR(EXTRACT(EPOCH FROM (now() - COALESCE(pt.asignado_en, pt.iniciada_en, pt.updated_at))) / 86400)::integer AS dias_pendiente
  FROM postulacion_etapa pt
  JOIN postulacion  po ON po.id = pt.postulacion_id
  JOIN proceso      pr ON pr.id = po.proceso_id
  JOIN proceso_etapa pe ON pe.id = pt.proceso_etapa_id
  JOIN etapa         e  ON e.id  = pe.etapa_id
 WHERE e.ejecucion = 'MANUAL'
   AND pt.estado IN ('PENDIENTE','EN_REVISION');

-- Embudo del dashboard: candidatos por etapa y estado
CREATE VIEW v_embudo_proceso AS
SELECT pe.proceso_id,
       pe.orden,
       e.codigo AS etapa,
       e.nombre AS etapa_nombre,
       COUNT(*) FILTER (WHERE pt.estado = 'APROBADA')                                AS aprobadas,
       COUNT(*) FILTER (WHERE pt.estado IN ('PENDIENTE','EN_CURSO','EN_REVISION'))   AS en_curso,
       COUNT(*) FILTER (WHERE pt.estado = 'REPROBADA')                               AS reprobadas
  FROM proceso_etapa pe
  JOIN etapa e ON e.id = pe.etapa_id
  LEFT JOIN postulacion_etapa pt ON pt.proceso_etapa_id = pe.id
 GROUP BY pe.id, pe.proceso_id, pe.orden, e.codigo, e.nombre;

-- Única fuente de datos de la landing pública: sin nombre, CI, contacto ni documentos
CREATE VIEW v_perfil_publico AS
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
  FROM ranking_resultado rr
  JOIN postulacion po          ON po.id = rr.postulacion_id
  JOIN convocatoria_talento ct ON ct.proceso_id = rr.proceso_id
  JOIN carrera c               ON c.id = ct.carrera_id
  JOIN facultad f              ON f.id = c.facultad_id
  JOIN egresado eg             ON eg.persona_id = po.persona_id AND eg.carrera_id = ct.carrera_id
 WHERE rr.visible
   AND rr.publicado_en IS NOT NULL
   AND rr.publicado_en + make_interval(months => ct.vigencia_perfil_meses) >= now()
   AND COALESCE((SELECT cs.otorgado
                   FROM consentimiento cs
                  WHERE cs.persona_id = po.persona_id AND cs.tipo = 'PUBLICACION_PERFIL'
                  ORDER BY cs.registrado_en DESC, cs.id DESC
                  LIMIT 1), FALSE);

-- =====================================================================
-- 11. DATOS INICIALES (catálogos fijos del sistema)
-- =====================================================================

INSERT INTO rol (codigo, nombre, descripcion) VALUES
  ('ADMIN',             'Administrador de la consultora', 'Configura procesos, pruebas, ranking y solicitudes'),
  ('AYUDANTE',          'Ayudante / junior de carrera',   'Revisa tareas manuales: CV, entrevista y antecedentes'),
  ('EGRESADO',          'Egresado UAGRM',                 'Postula al talento universitario'),
  ('CANDIDATO_EXTERNO', 'Candidato de empresa',           'Persona invitada por una empresa cliente'),
  ('EMPRESA',           'Empresa cliente',                'Solicita evaluaciones y recibe informes');

INSERT INTO etapa (codigo, nombre, ejecucion, descripcion) VALUES
  ('INSCRIPCION',               'Inscripción',                       'AUTOMATICA', 'Registro del postulante'),
  ('REQUISITOS',                'Requisitos',                        'AUTOMATICA', 'Documentos obligatorios y PPA mínimo'),
  ('ANALISIS_CV',               'Análisis de CV',                    'MANUAL',     'Revisión del currículum con rúbrica'),
  ('EVALUACION_TECNICA',        'Evaluación técnica',                'AUTOMATICA', 'PPA (flujo A) o prueba técnica de las funciones (flujo B)'),
  ('EVALUACION_PSICOLOGICA',    'Evaluación psicológica',            'AUTOMATICA', 'Batería de tests en línea'),
  ('HABILIDADES_BLANDAS',       'Medición de habilidades blandas',   'AUTOMATICA', 'Test o formulario de habilidades blandas'),
  ('ENTREVISTA',                'Entrevista',                        'MANUAL',     'Entrevista con formulario'),
  ('INVESTIGACION_ANTECEDENTES','Investigación de antecedentes',     'MANUAL',     'Verificación de empleos con referencias'),
  ('SELECCION',                 'Selección',                         'AUTOMATICA', 'Ranking y selección de terna o quinteto'),
  ('INFORME',                   'Informe y recomendaciones',         'MANUAL',     'Informe final a la empresa');

INSERT INTO dimension_evaluada (codigo, nombre, tipo, orden) VALUES
  ('SOCIABILIDAD',          'Sociabilidad',          'PERSONALIDAD',  1),
  ('CAPACIDAD_ESCOLAR',     'Capacidad escolar',     'PERSONALIDAD',  2),
  ('ESTABILIDAD_EMOCIONAL', 'Estabilidad emocional', 'PERSONALIDAD',  3),
  ('SUMISION',              'Sumisión',              'PERSONALIDAD',  4),
  ('RETRAIMIENTO',          'Retraimiento',          'PERSONALIDAD',  5),
  ('SUPEREGO',              'Superego',              'PERSONALIDAD',  6),
  ('TIMIDEZ',               'Timidez',               'PERSONALIDAD',  7),
  ('SEVERIDAD',             'Severidad',             'PERSONALIDAD',  8),
  ('CONFIANZA',             'Confianza',             'PERSONALIDAD',  9),
  ('OBJETIVIDAD',           'Objetividad',           'PERSONALIDAD', 10),
  ('INGENUIDAD',            'Ingenuidad',            'PERSONALIDAD', 11),
  ('SEGURIDAD_DE_SI_MISMO', 'Seguridad de sí mismo', 'PERSONALIDAD', 12),
  ('CONSERVADURISMO',       'Conservadurismo',       'PERSONALIDAD', 13),
  ('DEPENDENCIA_GRUPAL',    'Dependencia grupal',    'PERSONALIDAD', 14),
  ('INDIFERENCIA',          'Indiferencia',          'PERSONALIDAD', 15),
  ('TRANQUILIDAD',          'Tranquilidad',          'PERSONALIDAD', 16);

INSERT INTO prueba (codigo, nombre, tipo, descripcion) VALUES
  ('PPA',                        'Promedio ponderado histórico',   'CALCULADA', 'Evaluación técnica del flujo A: compara el PPA con el mínimo de la facultad'),
  ('CV_ANALISIS',                'Análisis de currículum',         'RUBRICA',   'Revisión manual con rúbrica'),
  ('TEST_PERSONALIDAD',          'Test de personalidad',           'TEST',      'Obligatorio; genera el gráfico de perfil'),
  ('TEST_INTELIGENCIA',          'Test de inteligencia',           'TEST',      'Obligatorio; matrices de figuras'),
  ('TEST_INTELIGENCIA_EMOCIONAL','Test de inteligencia emocional', 'TEST',      'Obligatorio'),
  ('TEST_LECTURA_COMPRENSIVA',   'Test de lectura comprensiva',    'TEST',      NULL),
  ('TEST_APTITUD_NUMERICA',      'Test de aptitud numérica',       'TEST',      NULL),
  ('TEST_HABILIDADES_BLANDAS',   'Test de habilidades blandas',    'TEST',      'Genera el gráfico de habilidades'),
  ('ENTREVISTA',                 'Entrevista',                     'RUBRICA',   'Formulario completado por el junior de la carrera');

INSERT INTO rubrica_criterio (prueba_id, nombre, puntaje_maximo, orden)
SELECT p.id, c.nombre, 10.00, c.orden
  FROM prueba p
  CROSS JOIN (SELECT 'Formación' AS nombre, 1 AS orden UNION ALL SELECT 'Experiencia', 2
        UNION ALL SELECT 'Habilidades', 3 UNION ALL SELECT 'Presentación', 4) c
 WHERE p.codigo = 'CV_ANALISIS';

INSERT INTO tipo_documento (codigo, nombre) VALUES
  ('HISTORICO_ACADEMICO', 'Histórico académico'),
  ('CEDULA_IDENTIDAD',    'Cédula de identidad'),
  ('FOTOGRAFIA',          'Fotografía'),
  ('CURRICULUM_VITAE',    'Currículum vitae');

INSERT INTO parametro_sistema (clave, valor, descripcion) VALUES
  ('top_n_defecto',               '5',   'Cantidad de candidatos del top al crear un proceso'),
  ('version_politica_privacidad', '1.0', 'Versión vigente de la política de tratamiento de datos'),
  ('dias_alerta_tarea_manual',    '5',   'Días tras los cuales una tarea manual se marca atrasada');

```
