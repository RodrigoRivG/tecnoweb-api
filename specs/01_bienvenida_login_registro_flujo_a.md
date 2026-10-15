# Spec 01 — Bienvenida, login y registro del egresado (flujo A)

Estado: **borrador para ejecutar** · Fecha: 2026-10-14 · Depende de: [basededatos.md](basededatos.md), [conversacion_sistema_seleccion.md](conversacion_sistema_seleccion.md)

## 1. Alcance

La aplicación Laravel es **solo API JSON** (Sanctum). Las pantallas las construye un frontend aparte; este spec define lo que ese frontend necesita para:

1. **Página de bienvenida** (pública, sin login): catálogos, convocatorias abiertas y perfiles públicos.
2. **Login único** para todos los roles, con sesión por token.
3. **Registro del egresado** hasta quedar **postulado** a una convocatoria: cuenta → perfil académico → postulación (filtro PPA) → documentos.

Fuera de alcance (specs posteriores): panel administrativo (crear convocatorias, editar `ppa_minimo`), validación de documentos por el ayudante, tests en línea, ranking, solicitud de información de empresas, flujo B, recuperación de contraseña.

## 2. Reglas de negocio

| # | Regla |
|---|---|
| R1 | Un usuario tiene **un solo rol**. El registro público crea siempre usuarios con rol `EGRESADO`. |
| R2 | La cuenta del egresado queda **`ACTIVO` al registrarse** y recibe token de inmediato (sin verificación de correo ni aprobación). |
| R3 | **El único filtro al postular es el PPA**: `egresado.ppa >= umbral`. El umbral es `facultad.ppa_minimo` de la facultad de la carrera; lo edita el administrador. Si el proceso configura la prueba `PPA` con `umbral_origen = 'FIJO'`, se usa `proceso_prueba.puntaje_minimo`. |
| R4 | Si el PPA no alcanza el umbral, la postulación **se rechaza (422) y no se crea nada**. El mensaje indica el mínimo exigido. |
| R5 | Solo se puede postular a convocatorias `ABIERTO`, de la **carrera del egresado**, con `fecha_apertura <= hoy` y (`fecha_cierre` nula o `>= hoy`). Una postulación por persona y convocatoria. |
| R6 | El consentimiento `TRATAMIENTO_DATOS` es **obligatorio** para registrarse. `PUBLICACION_PERFIL` es opcional (sin él, el perfil nunca aparece en la landing). Se registran con `version_politica` = `parametro_sistema.version_politica_privacidad` y la IP de origen. |
| R7 | Los documentos se suben **por postulación**, solo de los tipos exigidos en `requisito_documento` del proceso. Reemplazar un documento marca el anterior `es_vigente = false` (queda como historial). |
| R8 | Login: tras **5 intentos fallidos** seguidos el usuario pasa a `BLOQUEADO`. Un login exitoso reinicia `intentos_fallidos` y actualiza `ultimo_acceso`. Solo usuarios `ACTIVO` pueden entrar. |
| R9 | Si ya existe una `persona` con el mismo CI: con usuario → "ya registrado, inicie sesión"; sin usuario (p. ej. cargada por una empresa) → **no se vincula automáticamente**, se pide contactar a la consultora. |
| R10 | El PPA lo **declara el egresado** y el filtro de R3 se aplica sobre ese valor. La veracidad de los datos se verifica **en la entrevista presencial** (etapa `ENTREVISTA`, otro spec). |

## 3. Preparación técnica

### 3.1 Base de datos
- Entorno: PHP 8.5, Composer 2.9, Laravel 13, **PostgreSQL 18**.
- Conexión `pgsql`. `.env`: `DB_CONNECTION=pgsql`, base `talento`; tests en `talento_test`. **SQLite no sirve** (triggers PL/pgSQL, índices parciales, `INET`).
- El esquema se crea **solo con migraciones de Laravel**, con el formato de [02_formato_tablas_bd.md](02_formato_tablas_bd.md): una migración por tabla (las 43 del diseño), más las de esquema, triggers y vistas. [basededatos.md](basededatos.md) es la referencia de diseño; su script no se ejecuta.
- Catálogos iniciales con `CatalogoSeeder` (spec 02, sección 5.3).
- Tablas propias de Laravel (`migrations`, `personal_access_tokens`, `cache`, `jobs`) quedan en `public`. Se elimina la migración por defecto de `users`/`password_reset_tokens`/`sessions`.
- `config/database.php` → `pgsql.search_path = 'public,talento'`.
- Historial de etapas: toda escritura sobre `postulacion_etapa` va dentro de `DB::transaction()` precedida de `SET LOCAL app.usuario_id = '<id>'`. Se encapsula en un helper (p. ej. `App\Support\Auditoria::comoUsuario($id, fn () => ...)`).
### 3.2 Modelos (Eloquent)
- `Usuario` (`talento.usuario`) es el modelo autenticable: `getAuthPasswordName()` → `password_hash`, `HasApiTokens`. `config/auth.php` apunta el provider a `Usuario`.
- Modelos mínimos: `Rol`, `Usuario`, `Persona`, `Consentimiento`, `Archivo`, `Facultad`, `Carrera`, `AreaPreferencia`, `Egresado`, `Proceso`, `ConvocatoriaTalento`, `ProcesoEtapa`, `ProcesoPrueba`, `Prueba`, `Etapa`, `TipoDocumento`, `RequisitoDocumento`, `Postulacion`, `PostulacionEtapa`, `DocumentoPostulacion`, `ResultadoPrueba`, `ParametroSistema`.
- Formato, casts, PK y timestamps según [02_formato_tablas_bd.md](02_formato_tablas_bd.md) (secciones 6 y 7): `$table` explícito, timestamps por defecto en todos, nunca asignar `id`.

### 3.3 Convenciones de API
- Prefijo `/api/v1`. JSON con API Resources (`{ "data": ... }`). Errores de validación: 422 estándar de Laravel con mensajes en español (`APP_LOCALE=es`).
- Cliente: frontend **React + Vite** (repositorio aparte).
- Auth: `Authorization: Bearer <token>` (Sanctum personal access tokens). Middleware `rol:EGRESADO` para rutas del egresado.
- CORS (`config/cors.php`): rutas `api/*`, origen permitido desde `FRONTEND_URL` (local: `http://localhost:5173`, el puerto por defecto de Vite). Sin cookies (`supports_credentials = false`), porque se usa token.
- Rate limit: `login` y `registro` 5/min por IP+correo; públicos 60/min.
- Nunca se exponen `password_hash`, `token_hash`, `clave_almacenamiento` ni datos de otras personas.

### 3.4 Seeders
- Producción: `CatalogoSeeder` con los datos iniciales del diseño (roles, etapas, dimensiones, pruebas, criterios, tipos de documento, parámetros).
- El **catálogo oficial** (facultades, carreras, áreas, `ppa_minimo`) lo entrega el cliente y se registra **desde el panel de administración** (spec del panel). No hay seeder de catálogo académico para producción.
- `DemoSeeder` (solo local/testing), catálogo de prueba:
  - Facultades: FICCT (Ing. Informática, Ing. de Sistemas, Redes y Telecomunicaciones), Ciencias Jurídicas (Derecho, con áreas Penal, Civil, Laboral), Ciencias Económicas (Contaduría Pública, Administración de Empresas). `ppa_minimo` 51 salvo una con 60 para probar.
  - Un usuario `ADMIN` y una convocatoria `ABIERTO` por carrera de FICCT con etapas `INSCRIPCION, REQUISITOS, EVALUACION_TECNICA (prueba PPA, umbral FACULTAD), ANALISIS_CV, ...` y requisitos `HISTORICO_ACADEMICO, CEDULA_IDENTIDAD, FOTOGRAFIA, CURRICULUM_VITAE`.

## 4. Endpoints

### 4.1 Bienvenida (públicos, sin token)

**`GET /api/v1/publico/facultades`** — facultades activas con sus carreras activas y áreas de preferencia (alimenta filtros y el formulario de registro).
```json
{ "data": [ { "id": 1, "nombre": "...", "sigla": "FICCT",
  "carreras": [ { "id": 3, "nombre": "Ingeniería Informática", "areas": [ { "id": 7, "nombre": "..." } ] } ] } ] }
```
No expone `ppa_minimo` (dato administrativo).

**`GET /api/v1/publico/convocatorias?facultad_id=&carrera_id=`** — convocatorias que cumplen R5 (abiertas y en fecha). Campos: `proceso_id`, `codigo`, `nombre`, `carrera`, `facultad`, `gestion`, `numero`, `fecha_apertura`, `fecha_cierre`, `ppa_minimo` (umbral efectivo según R3; aquí sí se muestra para que el egresado sepa si califica).

**`GET /api/v1/publico/perfiles?facultad_id=&carrera_id=&solo_top=&page=`** — lee **solo** de `v_perfil_publico`, paginado (15). Campos: `codigo_candidato`, `facultad`, `carrera`, `gestion`, `anio_egreso`, `posicion`, `puntaje_total`, `es_top`, `publicado_en`. Vacío hasta que exista un ranking publicado (otro spec).

### 4.2 Autenticación

**`POST /api/v1/auth/login`** `{ correo, password, dispositivo? }`
- 200: `{ data: { token, usuario: { id, correo, rol: { codigo, nombre }, persona: { nombres, apellido_paterno, apellido_materno } } } }`
- 422 credenciales inválidas (mensaje genérico, no revela si el correo existe) · 423 `BLOQUEADO` · 403 `PENDIENTE`/`INACTIVO`. Aplica R8.

**`POST /api/v1/auth/logout`** (token) — revoca el token actual. 204.

**`GET /api/v1/auth/me`** (token) — usuario + rol + persona; para `EGRESADO` incluye sus perfiles `egresados[]` (carrera, facultad, área, registro universitario, año, PPA).

### 4.3 Registro del egresado

**`POST /api/v1/registro/egresado`** (público) — en **una transacción** crea `usuario` (rol EGRESADO, `ACTIVO`), `persona` (vinculada), `egresado` y `consentimiento`(s). Responde 201 con el mismo cuerpo que el login (token incluido).

| Campo | Regla |
|---|---|
| `correo` | requerido, email, ≤190, único en `usuario.correo` |
| `password`, `password_confirmation` | requerido, mín. 8, letras y números, confirmado |
| `nombres` | requerido, ≤100 |
| `apellido_paterno`, `apellido_materno` | ≤60; **al menos uno** requerido |
| `ci_numero` | requerido, ≤15, solo dígitos; con `ci_complemento` (≤3, opcional) único — ver R9 |
| `ci_expedido` | opcional, `LP CB SC OR PT CH TJ BE PD` |
| `fecha_nacimiento` | requerida, fecha, edad ≥ 18 |
| `telefono` | opcional, ≤20 |
| `carrera_id` | requerido, carrera activa de facultad activa |
| `area_preferencia_id` | opcional, debe pertenecer a `carrera_id` |
| `registro_universitario` | requerido, ≤20, único |
| `anio_egreso` | requerido, entero 1950–año actual |
| `ppa` | requerido, decimal 0–100, 2 decimales |
| `acepta_tratamiento_datos` | requerido, `true` (R6) |
| `acepta_publicacion_perfil` | booleano, por defecto `false` |

`persona.correo_contacto` = `correo`. Las violaciones de UNIQUE que lleguen a la BD (carrera concurrente) se traducen a 422, nunca 500.

### 4.4 Postulación (token, rol EGRESADO)

**`GET /api/v1/egresado/convocatorias`** — convocatorias abiertas de **sus** carreras con, por cada una: `ppa_minimo`, `cumple_ppa` (bool), `ya_postulado` (bool) y `postulacion_id` si existe. Permite al frontend deshabilitar el botón y explicar por qué.

**`POST /api/v1/egresado/postulaciones`** `{ proceso_id }`
1. Valida R5 (si no: 422 con el motivo).
2. Calcula el umbral (R3) y compara con el PPA del egresado en la carrera de la convocatoria. No cumple → **422 R4**: `"Tu PPA (48.50) no alcanza el mínimo de 51.00 exigido por la facultad."`
3. Cumple → en una transacción con `app.usuario_id`:
   - `postulacion` (`estado = EN_PROCESO`, `codigo` seudónimo único, p. ej. `T26-7KQ9XM` = flujo + gestión + 6 caracteres aleatorios sin ambiguos; reintenta si colisiona).
   - `resultado_prueba` de la prueba `PPA` si el proceso la tiene configurada: `puntaje_bruto = ppa`, `puntaje_maximo = 100`, `umbral_aplicado = umbral`, `aprobada = true`. Deja trazado con qué mínimo se juzgó.
   - Una `postulacion_etapa` por cada `proceso_etapa` activa: `INSCRIPCION` y la etapa que contiene la prueba PPA → `APROBADA`; la primera etapa restante por `orden` → `PENDIENTE`; las demás → `BLOQUEADA`.
4. 201 con el detalle de la postulación (ver siguiente).

**`GET /api/v1/egresado/postulaciones`** — lista de sus postulaciones (código, convocatoria, estado, fecha).

**`GET /api/v1/egresado/postulaciones/{id}`** — solo propia (policy, 404 si no). Incluye: convocatoria, `estado`, `etapas[]` (nombre, orden, estado), `requisitos[]` (tipo, obligatorio, documento vigente si existe con su `estado` y `observacion`) y `requisitos_completos` (bool).

### 4.5 Documentos (token, rol EGRESADO)

**`POST /api/v1/egresado/postulaciones/{id}/documentos`** (multipart) `{ tipo_documento: "CURRICULUM_VITAE", archivo }`
- El tipo debe estar en `requisito_documento` del proceso (R7). PDF, JPG o PNG; máx. 5 MB (`FOTOGRAFIA`: solo JPG/PNG).
- Postulación en estado `EN_PROCESO` y la etapa `REQUISITOS` no `APROBADA`/`REPROBADA`.
- Guarda en disco privado `local` bajo `postulaciones/{codigo}/{uuid}.{ext}`; crea `archivo` (nombre original, clave, mime, tamaño, `hash_sha256`, `subido_por`) y `documento_postulacion` (`PENDIENTE`, `es_vigente = true`), marcando el vigente anterior del mismo tipo como `es_vigente = false` en la misma transacción.
- Si con esto quedan todos los obligatorios cargados y `REQUISITOS` está `PENDIENTE` → pasa a `EN_REVISION` (con `app.usuario_id`).
- 201 con el documento.

**`GET /api/v1/egresado/documentos/{id}/archivo`** — descarga su propio archivo (stream, nunca URL pública).

## 5. Pruebas (PHPUnit, feature, sobre PostgreSQL)

- **Bienvenida:** facultades sin `ppa_minimo`; convocatorias filtran cerradas/fuera de fecha; perfiles solo desde la vista.
- **Login:** éxito devuelve token y rol; contraseña errónea suma intentos; al 5.º queda `BLOQUEADO` (423); `PENDIENTE`/`INACTIVO` → 403; logout revoca.
- **Registro:** caso feliz crea usuario+persona+egresado+consentimientos y devuelve token; sin apellidos, menor de edad, área de otra carrera, sin consentimiento, correo/CI/registro duplicados → 422; CI de persona sin usuario → 422 (R9).
- **Postulación:** PPA igual al mínimo → 201 (límite inclusivo); PPA menor → 422 sin filas creadas; cambio de `ppa_minimo` en facultad se refleja en la siguiente postulación; umbral `FIJO` usa `puntaje_minimo`; carrera ajena, cerrada o duplicada → 422; etapas creadas con los estados esperados y `historial_postulacion_etapa` con `usuario_id` del egresado; `resultado_prueba` guarda `umbral_aplicado`.
- **Documentos:** tipo no requerido → 422; reemplazo deja un solo vigente; completar obligatorios pasa `REQUISITOS` a `EN_REVISION`; otro egresado no puede ver ni subir (404).
- **Seguridad:** ningún endpoint devuelve `password_hash` ni datos de terceros; rutas de egresado con otro rol → 403.

## 6. Orden de ejecución

1. Prerrequisitos de [CLAUDE.md](../CLAUDE.md): PHP/Composer, `composer require laravel/boost --dev`, `php artisan boost:install`.
2. Base de datos (3.1) + modelos (3.2) + seeders (3.4).
3. Autenticación (4.2) → bienvenida (4.1) → registro (4.3) → postulación (4.4) → documentos (4.5), cada bloque con sus pruebas.
4. `vendor/bin/pint` y `php artisan test` en verde.

## 7. Decisiones confirmadas (2026-10-14)

- **R4:** PPA insuficiente → se rechaza y **no se guarda nada**.
- **R2:** cuenta activa **sin verificación** de correo.
- **R10:** PPA declarado libremente; se verifica en la entrevista presencial.
- **`ppa_minimo`:** se edita en el spec del panel; mientras tanto viene del `DemoSeeder`.
- **Catálogo académico:** lo da el cliente y se carga desde el panel.
- **Límites:** edad mínima 18; archivos de hasta 5 MB en PDF/JPG/PNG; una carrera por egresado en el registro (agregar otra queda para el perfil del egresado).

Pendiente: nombre, usuario y contraseña de las bases `talento` y `talento_test` (van en `.env`, no en el spec).
