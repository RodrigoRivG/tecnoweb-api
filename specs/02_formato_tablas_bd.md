# Spec 02 — Formato de las tablas de la base de datos

Estado: **borrador para ejecutar** · Fecha: 2026-10-14 · Depende de: [basededatos.md](basededatos.md) · Lo aplican: [01_bienvenida_login_registro_flujo_a.md](01_bienvenida_login_registro_flujo_a.md) y todos los specs siguientes

## 1. Objetivo

Fijar **el formato de todas las tablas** del sistema y **cómo se crean y mapean en Laravel**.

[basededatos.md](basededatos.md) es la **referencia de diseño** (entidades, relaciones, reglas, triggers, vistas). Su script **no se ejecuta**: el esquema se construye **únicamente con migraciones de Laravel** que aplican este formato. Las diferencias con el script están en la sección 4.3.

## 2. Reglas obligatorias

| # | Regla |
|---|---|
| F1 | **Nombres de tabla en español, minúscula y `snake_case`**, en singular como en el diseño (`postulacion_etapa`). |
| F2 | **Atributos (columnas) en español, minúscula y `snake_case`**. La única excepción son los timestamps. |
| F3 | **Todas las tablas tienen `created_at` y `updated_at`**, incluidos catálogos, tablas puente y detalles. |
| F4 | **Todas las tablas, restricciones, índices, triggers y vistas se crean a partir de migraciones.** No se ejecuta SQL suelto ni se modifica la BD a mano. |

Excepción: las tablas propias del framework (`migrations`, `personal_access_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`) conservan su nombre en inglés porque Laravel y Sanctum las esperan así. Viven en el esquema `public`, separadas del negocio.

## 3. Convenciones

### 3.1 Nombres

| Elemento | Formato | Ejemplo |
|---|---|---|
| Esquema | `talento` para el negocio; `public` para el framework | `talento.postulacion` |
| Tabla | F1 | `convocatoria_talento` |
| Columna | F2 | `fecha_apertura`, `ppa_minimo` |
| FK | `<entidad>_id`; auditoría con verbo: `creado_por`, `validado_por`, `subido_por`, `resuelto_por`, `publicado_por`, `verificado_por`, `actualizado_por`, `elaborado_por` | `carrera_id`, `validado_por` |
| Booleano | `es_<x>` o adjetivo | `es_vigente`, `activa`, `obligatoria` |
| Fecha de un evento de negocio | `<participio>_en` | `asignado_en`, `emitido_en` |
| Código de negocio | `codigo` (`UPPER_SNAKE` en catálogos) | `TEST_PERSONALIDAD` |
| Restricciones | `fk_<tabla>_<referida>` · `uq_<tabla>_<campos>` · `ix_<tabla>_<campos>` · `ck_<tabla>_<regla>` · triggers `trg_<tabla>_<bi\|ai\|au\|updated>` · funciones `fn_<tabla>_<momento>` · vistas `v_<nombre>` | `ck_egresado_ppa` |

Las restricciones se nombran con la tabla completa, salvo que superen los 63 caracteres que admite PostgreSQL; en ese caso se abrevia como en el diseño (`fk_criterio_prueba`).

### 3.2 Claves primarias
- **Catálogos**: `id INTEGER GENERATED ALWAYS AS IDENTITY`.
- **Transaccionales**: `id BIGINT GENERATED ALWAYS AS IDENTITY`.
- **Subtipos 1:1**: la PK es la FK al padre (`convocatoria_talento.proceso_id`).
- **Tablas puente y detalles**: PK compuesta por sus FK (`ayudante_carrera`).
- **Nunca** se inserta un `id` explícito.

### 3.3 Tipos

| Dato | Tipo | Nota |
|---|---|---|
| Texto corto | `VARCHAR(n)` con límite real | Correos `VARCHAR(190)` |
| Texto largo | `TEXT` | |
| Estados y enumerados | `VARCHAR(n)` + `CHECK (col IN (...))`, valores en `UPPER_SNAKE` | No se usa `CREATE TYPE ... ENUM` |
| Puntajes | `NUMERIC(5,2)` en escala **0–100**; dimensiones **0–10** `NUMERIC(4,2)`; brutos `NUMERIC(7,2)` | |
| Fecha y hora | `TIMESTAMPTZ` (UTC) | `TIMESTAMPTZ(3)` en el historial |
| Fecha de calendario | `DATE` | |
| Contadores y órdenes | `SMALLINT` | |
| Booleano | `BOOLEAN NOT NULL DEFAULT ...` | Anulable solo para respuestas sí/no aún sin contestar |
| IP | `INET` | |
| Hash | `CHAR(64)` | SHA-256 en hexadecimal |
| Calculados | `GENERATED ALWAYS AS (...) STORED` | `puntaje_normalizado`, `duracion_seg` |

### 3.4 Integridad
- Toda FK lleva índice (vale la PK o un `UNIQUE` que la cubra).
- `ON DELETE` por defecto **RESTRICT**; `CASCADE` solo para hijos que no existen sin su padre; `SET NULL` para auditoría.
- **Sin borrado físico ni `deleted_at`**: se usa `activo`/`activa` o un `estado`.
- **Discriminador** de subtipos: columna `tipo_<padre>` con `DEFAULT` + `CHECK` fijo + FK compuesta a `(id, tipo)`.
- "Solo uno vigente o principal": **índice único parcial**.
- Reglas entre tablas que una FK no expresa: trigger `BEFORE INSERT` con `RAISE EXCEPTION` en español.
- Toda tabla lleva `COMMENT ON TABLE`.
- **Correo único sin distinguir mayúsculas**: índice único sobre `lower(correo)` y la aplicación lo guarda en minúsculas.

## 4. Timestamps

### 4.1 Formato
```
created_at TIMESTAMPTZ NOT NULL DEFAULT now()
updated_at TIMESTAMPTZ NOT NULL DEFAULT now()   -- + trigger trg_<tabla>_updated
```
- Se escriben así, **`created_at` y `updated_at`**, que son los nombres que Laravel maneja automáticamente.
- El trigger `set_updated_at()` va en **todas** las tablas: cubre las actualizaciones que no pasan por Eloquent (`DB::table()->update()`).
- `historial_postulacion_etapa` conserva la precisión de milisegundos: `created_at TIMESTAMPTZ(3)`.

### 4.2 Timestamps frente a fechas de negocio
Los timestamps registran **cuándo se escribió la fila**. Las fechas de negocio registran **cuándo ocurrió algo** y pueden diferir (datos importados, correcciones). Por eso conviven:

| Tabla | Fecha de negocio que se mantiene |
|---|---|
| `consentimiento` | `registrado_en` |
| `intento_prueba` | `iniciado_en`, `finalizado_en` |
| `respuesta_intento` | `respondida_en` |
| `resultado_prueba` | `calificado_en` |
| `invitacion_postulacion` | `enviada_en`, `expira_en`, `aceptada_en` |
| `documento_postulacion` | `validado_en` |
| otras | `asignado_en`, `iniciada_en`, `finalizada_en`, `publicado_en`, `emitido_en`, `resuelto_en`, `entregada_en`, `completada_en`, `ultimo_acceso`... |

### 4.3 Cambios respecto al script de diseño

| Cambio | Tablas |
|---|---|
| `creado_en` **se renombra** a `created_at` (era solo un timestamp de alta) | `archivo`, `documento_postulacion`, `historial_postulacion_etapa` |
| Se agrega `updated_at` | `prueba`, `solicitud_informacion`, `archivo`, `documento_postulacion`, `historial_postulacion_etapa` |
| Se agrega `created_at` | `postulacion_etapa`, `parametro_sistema` |
| Se agregan `created_at` y `updated_at` | Las 30 tablas restantes que no tenían ninguno (catálogos, puentes, detalles, subtipos, resultados, informes) |
| Trigger `trg_<tabla>_updated` | Las 43 tablas (antes 8) |
| `uq_usuario_correo` pasa a ser índice único sobre `lower(correo)` | `usuario` |
| Se corrigen los comentarios heredados de MySQL | `resultado_prueba`, `contacto_empresa` |

Las vistas que usaban `pt.updated_at` (`v_bandeja_tareas`) siguen funcionando igual. Cualquier referencia a `creado_en` en vistas, triggers o índices pasa a `created_at` (`ix_historial_etapa`).

Al ejecutar este spec, [basededatos.md](basededatos.md) se actualiza con estos cambios o se marca como superado por las migraciones.

## 5. Migraciones

### 5.1 Organización
- **Una migración por tabla**, en el orden de dependencias del diseño (ya viene ordenado en el script): `rol` → `usuario` → `persona` → ... → `parametro_sistema`.
- Nombre: `AAAA_MM_DD_HHMMSS_create_<tabla>_table.php`, con la tabla calificada `talento.<tabla>`.
- Migraciones **aparte**, después de las tablas:
  1. `create_talento_schema` (la primera de todas): `CREATE SCHEMA IF NOT EXISTS talento` y la función `set_updated_at()`.
  2. `create_talento_triggers`: las 10 funciones de negocio y sus triggers.
  3. `create_talento_views`: las 6 vistas.
- Toda migración tiene un `down()` que deshace exactamente su `up()`. La primera, en su `down()`, elimina el esquema con `DROP SCHEMA talento CASCADE`.
- Una vez desplegada, **una migración no se edita**: los cambios van en una migración nueva (`add_<columna>_to_<tabla>_table`, `alter_...`).

### 5.2 Qué va en Schema Builder y qué en `DB::statement`

| Schema Builder | `DB::statement` (dentro de la misma migración) |
|---|---|
| Columnas, tipos, `NOT NULL`, `DEFAULT` | `CHECK` con nombre `ck_` |
| PK identity, PK compuesta (`$table->primary([...], 'nombre')`) | Índices únicos parciales y sobre expresiones (`lower(correo)`) |
| FK con nombre `fk_` y `ON DELETE` | `COMMENT ON TABLE` |
| `UNIQUE` e índices con nombre | Trigger `trg_<tabla>_updated` |
| Columnas generadas (`->storedAs()`) | FK compuestas que Schema Builder no nombre bien |

### 5.3 Datos iniciales
Los catálogos fijos del diseño (roles, etapas, 16 dimensiones, pruebas, criterios de CV, tipos de documento, parámetros) se cargan con **`CatalogoSeeder`**, idempotente (`upsert` por `codigo` o `clave`), que corre también en producción (`php artisan db:seed --class=CatalogoSeeder --force`). Los datos de prueba van en `DemoSeeder` (spec 01), solo en local y testing.

### 5.4 Plantilla

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talento.ejemplo', function (Blueprint $table) {
            $table->id()->generatedAs()->always();                 // BIGINT GENERATED ALWAYS AS IDENTITY
            $table->foreignId('postulacion_id');
            $table->string('estado', 10)->default('PENDIENTE');
            $table->decimal('puntaje', 5, 2)->nullable();
            $table->boolean('es_vigente')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('postulacion_id', 'fk_ejemplo_postulacion')
                  ->references('id')->on('talento.postulacion');
            $table->index('postulacion_id', 'ix_ejemplo_postulacion');
        });

        DB::statement("ALTER TABLE talento.ejemplo ADD CONSTRAINT ck_ejemplo_estado CHECK (estado IN ('PENDIENTE','CERRADO'))");
        DB::statement("COMMENT ON TABLE talento.ejemplo IS 'Finalidad de la tabla'");
        DB::statement('CREATE TRIGGER trg_ejemplo_updated BEFORE UPDATE ON talento.ejemplo FOR EACH ROW EXECUTE FUNCTION talento.set_updated_at()');
    }

    public function down(): void
    {
        Schema::dropIfExists('talento.ejemplo');
    }
};
```

Para no repetir las dos columnas y el trigger en 43 migraciones, se crea un helper `App\Support\Migraciones\Talento` con `timestamps(Blueprint $t)` y `triggerUpdatedAt(string $tabla)`.

> Verificar al ejecutar: que `->generatedAs()->always()` produzca `GENERATED ALWAYS AS IDENTITY` en Laravel 13.x. Si no, la PK se define con `DB::statement`.

## 6. Mapeo a Laravel

### 6.1 Modelos
- Nombre en `StudlyCase`, singular y en español: `Usuario`, `PostulacionEtapa`.
- **Siempre `protected $table`** explícito (el pluralizador es inglés: `Persona` → `personas`). Se usa el nombre sin calificar; lo resuelve `search_path = public,talento`.
- **Timestamps por defecto en todos los modelos**: no se configura nada (F3).
- `$fillable` explícito, nunca con `id`, columnas generadas ni discriminadores.
- `$hidden`: `password_hash`, `token_hash`, `clave_almacenamiento`, `hash_sha256`.
- Relaciones en español: singular para `belongsTo`/`hasOne`, plural para `hasMany`; FK de auditoría explícitas: `belongsTo(Usuario::class, 'validado_por')`.

### 6.2 Casts

| Tipo PostgreSQL | Cast |
|---|---|
| `NUMERIC(p,2)` | `'decimal:2'` |
| `TIMESTAMPTZ` | `'immutable_datetime'` |
| `DATE` | `'immutable_date'` |
| `BOOLEAN` | `'boolean'` |
| `VARCHAR + CHECK` | **PHP backed enum** en `app/Enums/`, con test que lo compara contra el `CHECK` en `pg_constraint` |
| `INET`, `CHAR(64)` | string |

### 6.3 Claves primarias no estándar

| Caso | Tablas | Configuración |
|---|---|---|
| PK = FK del padre | `convocatoria_talento`, `solicitud_empresa`, `investigacion_antecedentes` | `$primaryKey = '<fk>'`, `$incrementing = false` |
| PK de texto | `parametro_sistema` (`clave`) | `$primaryKey = 'clave'`, `$keyType = 'string'`, `$incrementing = false` |
| PK compuesta con datos | `perfil_requerido`, `requisito_documento`, `informe_candidato` | `belongsToMany(...)->using(Pivot)->withPivot(...)->withTimestamps()` |
| PK compuesta puente | `ayudante_carrera` | `belongsToMany(...)->withTimestamps()` |
| PK compuesta de detalle | `respuesta_intento`, `calificacion_criterio`, `resultado_dimension` | `createMany`/`upsert` desde el padre; `where(...)->update(...)` |

`->withTimestamps()` es obligatorio en toda relación `belongsToMany` para que se llenen `created_at` y `updated_at` en las tablas puente.

### 6.4 Solo lectura
- `historial_postulacion_etapa`: la escriben solo los triggers; su modelo no tiene `$fillable`.
- Vistas `v_*`: modelos en `App\Models\Vistas\`, sin escritura.

### 6.5 Zona horaria
`app.timezone = 'UTC'` y conexión `pgsql` con `'timezone' => 'UTC'`. La API devuelve `TIMESTAMPTZ` en ISO 8601 UTC y `DATE` como `Y-m-d`; el frontend convierte a `America/La_Paz`.

### 6.6 Errores de la BD → HTTP
Las `QueryException` se traducen por SQLSTATE en `bootstrap/app.php` con un mapa `restricción → campo/mensaje` en `lang/es/bd.php`:

| SQLSTATE | Origen | Respuesta |
|---|---|---|
| `23505` | `UNIQUE` | 422 (`uq_usuario_correo` → "El correo ya está registrado") |
| `23503` | FK | 422 "Referencia inválida", o 409 si se intentó borrar algo con dependencias |
| `23514` | `CHECK` | 422 con el campo deducido de `ck_<tabla>_<campo>` |
| `P0001` | Triggers | 422 con el mensaje del trigger |

## 7. Inventario: las 23 tablas del spec 01

Todas con `created_at` y `updated_at`.

| Tabla | Modelo | PK | Enum(s) PHP |
|---|---|---|---|
| `rol` | `Rol` | int | — |
| `usuario` | `Usuario` | bigint | `EstadoUsuario` |
| `persona` | `Persona` | bigint | `DepartamentoCi` |
| `consentimiento` | `Consentimiento` | bigint | `TipoConsentimiento` |
| `archivo` | `Archivo` | bigint | — |
| `facultad` | `Facultad` | int | — |
| `carrera` | `Carrera` | int | — |
| `area_preferencia` | `AreaPreferencia` | int | — |
| `egresado` | `Egresado` | bigint | — |
| `etapa` | `Etapa` | int | `EjecucionEtapa` |
| `prueba` | `Prueba` | int | `TipoPrueba` |
| `proceso` | `Proceso` | bigint | `TipoProceso`, `EstadoProceso` |
| `convocatoria_talento` | `ConvocatoriaTalento` | `proceso_id` | — |
| `proceso_etapa` | `ProcesoEtapa` | bigint | — |
| `proceso_prueba` | `ProcesoPrueba` | bigint | `UmbralOrigen` |
| `tipo_documento` | `TipoDocumento` | int | — |
| `requisito_documento` | pivot `RequisitoDocumento` | compuesta | — |
| `postulacion` | `Postulacion` | bigint | `EstadoPostulacion` |
| `postulacion_etapa` | `PostulacionEtapa` | bigint | `EstadoEtapa` |
| `historial_postulacion_etapa` | `HistorialPostulacionEtapa` (lectura) | bigint | `EstadoEtapa` |
| `documento_postulacion` | `DocumentoPostulacion` | bigint | `EstadoDocumento` |
| `resultado_prueba` | `ResultadoPrueba` | bigint | `MotivoNoAprobacion` |
| `parametro_sistema` | `ParametroSistema` | `clave` | — |

Aunque el spec 01 solo usa estas 23, **se migran las 43 tablas**, porque la vista `v_perfil_publico` depende de `ranking_resultado` y así los specs siguientes parten del esquema completo.

## 8. Checklist de una tabla nueva

- [ ] Nombre en español, minúscula, `snake_case`, singular (F1).
- [ ] Columnas en español, minúscula, `snake_case`; solo `created_at`/`updated_at` en inglés (F2).
- [ ] `created_at` y `updated_at` `TIMESTAMPTZ NOT NULL DEFAULT now()` + `trg_<tabla>_updated` (F3).
- [ ] Creada con su propia migración, con `down()` reversible (F4).
- [ ] PK identity (`INTEGER` en catálogo, `BIGINT` en transaccional).
- [ ] FK con nombre `fk_`, índice y `ON DELETE` justificado.
- [ ] Enumerados `VARCHAR` + `CHECK` `ck_`, más el enum PHP y su test.
- [ ] Puntajes en 0–100 con `NUMERIC(5,2)`.
- [ ] Sin `deleted_at`.
- [ ] `COMMENT ON TABLE`.
- [ ] Modelo con `$table`, `$fillable`, casts; fila en el inventario del spec que la usa.

## 9. Pruebas

- `migrate:fresh` crea las 43 tablas, 6 vistas, 10 triggers de negocio y 43 de `updated_at` en `talento`; `migrate:rollback` total deja el esquema vacío.
- **Test de formato** que recorre `information_schema` y falla si alguna tabla de `talento`: no tiene `created_at` y `updated_at` `NOT NULL`; tiene una columna o tabla con mayúsculas o fuera de `snake_case`; no tiene el trigger `trg_<tabla>_updated`. Así F1–F3 se verifican solas en cada cambio futuro.
- Un `DB::table(...)->update()` actualiza `updated_at` (por el trigger).
- Cada modelo del inventario crea y lee una fila con su factory; insertar un `id` explícito falla; `puntaje_normalizado` y `duracion_seg` los calcula la BD.
- Enums PHP = `CHECK` de la BD.
- `Ana@X.com` y `ana@x.com` → la segunda devuelve 422.
- `23505`, `23514` y `P0001` → 422 con el mensaje mapeado, nunca 500.
- `CatalogoSeeder` dos veces seguidas no duplica filas.

## 10. Decisiones a confirmar

Mientras no se confirmen, se ejecuta con la opción indicada en cada punto. Motor: **PostgreSQL 18**; las columnas generadas se declaran siempre `STORED` explícito, porque en PostgreSQL 18 el valor por defecto pasó a ser `VIRTUAL`.

- Tablas en **singular**, como en el diseño (`postulacion`, no `postulaciones`).
- Negocio en el esquema **`talento`** y framework en `public`.

- Se interpreta "update_at" como **`updated_at`**, el nombre que Laravel maneja automáticamente.
- Las fechas de negocio de la sección 4.2 (p. ej. `consentimiento.registrado_en`) **se mantienen** junto a los timestamps. Alternativa: eliminar las que coinciden siempre con `created_at`.
- Tablas del framework en inglés en el esquema `public` (sección 2).
- Catálogos iniciales por seeder idempotente (recomendado) frente a insertarlos dentro de las migraciones.
