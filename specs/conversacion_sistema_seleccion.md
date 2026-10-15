# Sistematización de herramientas métricas para la selección de personal
Resumen de la conversación — Cristian Gedalge (UAGRM) · Octubre 2026

---

## 1. Contexto y objetivo
Sistema para una **consultora** que evalúa y selecciona personal con métricas objetivas. Dos flujos:

### Flujo A — Talento universitario (egresados titulados de la UAGRM)
1. El egresado se registra (datos personales, **facultad y carrera**), sube documentos y su **PPA** (promedio ponderado histórico; el mínimo se define **por facultad** en el panel administrativo).
2. Etapas **eliminatorias** (si no cumple una, no avanza):
   - Requisitos / documentos
   - Análisis de CV (manual, nivel junior)
   - Evaluación técnica (PPA vs. mínimo de su facultad)
   - Tests psicológicos: personalidad (16 factores), inteligencia (tests de figuras que el cliente tiene impresos y quiere subir con sus respuestas), inteligencia emocional y 3 de habilidades. Cada uno con **nota mínima y tiempo mínimo**.
   - Habilidades blandas
   - Entrevista (manual)
   - Selección
3. El sistema genera un **ranking**; el **top 5** es configurable por el administrador.
4. **Landing pública** por facultad y carrera: las empresas ven solo datos **no sensibles**; si quieren más información, la solicitan a la consultora, que la entrega.

### Flujo B — Candidatos de la empresa
1. La empresa crea una **solicitud** con funciones del cargo, perfil psicológico ideal, CV y habilidades requeridas.
2. La empresa registra a sus candidatos.
3. Etapas: CV, evaluación técnica (derivada de las funciones), batería de 6 tests, investigación de antecedentes laborales.
4. Se arma terna/quinteto y se emite un **informe con recomendaciones** y un gráfico **perfil ideal vs. perfil real**.

## 2. Respuestas a las dudas iniciales
- Toda página pública necesita landing: sí. Un **único login** para estudiantes, administrativos y ayudantes, con **roles** que determinan qué ve cada uno; la landing pública es aparte y no requiere login.
- La consultora necesita un **panel administrativo** (configuración de mínimos por facultad, tests, rúbricas, top N, asignación de ayudantes).
- Las partes **manuales** (CV, entrevista) se gestionan con **bandeja de tareas** por evaluador, rúbricas con criterios y estados por etapa, con historial.
- Tests impresos del cliente: se digitalizan cargando el banco de preguntas con sus opciones y puntaje; el estudiante responde en el sistema con control de tiempo.

## 3. Prompts para Google Stitch
Se entregaron prompts detallados (flujo A y flujo B) para el boceto. Se pegan **uno por pantalla/conversación**, no todos juntos. Nota: el test de personalidad tiene **16 factores** (incluye "Indiferencia"); un prompt listaba 15 y se corrigió.

## 4. Diseño de la base de datos (MySQL 8)
**Tecnología:** MySQL 8.0.16+, InnoDB, utf8mb4, fechas en UTC, snake_case en español, notas normalizadas a 0–100.

**Resultado:** 43 tablas, 73 claves foráneas, 10 triggers, 6 vistas, probado en un MySQL real (flujos A y B + 29 pruebas negativas).

### Módulos
1. **Seguridad y trazabilidad:** `rol`, `usuario`, `persona`, `consentimiento`, `archivo`, `parametro_sistema`
2. **Catálogos académicos:** facultad, carrera, área de preferencia, etc.
3. **Procesos:** supertipo `proceso` (TALENTO/EMPRESA) con subtipos `convocatoria_talento` y `solicitud_empresa`; `proceso_etapa`, `proceso_prueba`, `perfil_requerido`, `requisito_documento`
4. **Postulación y avance:** `postulacion`, `postulacion_etapa`, `documento_postulacion`, `invitacion_postulacion`, historial
5. **Evaluación:** `prueba` (TEST / RUBRICA / CALCULADA), preguntas y opciones con puntaje, `intento_prueba`, `respuesta_intento`, `resultado_prueba`, `resultado_dimension`, `calificacion_criterio`
6. **Resultados:** `ranking_resultado`, `informe_final`, `informe_candidato`
7. **Empresa:** `solicitud_informacion`, `contacto_empresa`, `antecedente_laboral`, `investigacion_antecedentes`

### Decisiones clave
- **Un solo motor de evaluación** para ambos flujos (supertipo `proceso`), con integridad por FK compuesta y discriminador.
- **Etapa** (compuerta, manual/automática) distinta de **prueba** (unidad que puntúa).
- Umbrales por proceso: fijo o **por facultad**; tiempo mínimo/máximo; máximo de intentos.
- 3FN con desnormalizaciones deliberadas: snapshots en `resultado_prueba`, ranking congelado, columnas generadas (`puntaje_normalizado`, `duracion_seg`, etc.).
- Opciones con `puntaje` (no un flag correcto/incorrecto).
- Datos sensibles separados; la vista `v_perfil_publico` solo expone lo no sensible.

### Roles (decisión de Cristian)
- **Un usuario = un rol** (`usuario.rol_id`). Si un egresado pasa a ayudante, se actualiza su `rol_id`.
- Se **eliminaron** `usuario_rol` y `bitacora_auditoria`.

### Triggers (10)
8 de validación `BEFORE INSERT` (coherencia entre tablas que las FK no cubren) y 2 `AFTER` que guardan historial usando `@app_usuario_id`.

### Vistas (6)
`v_perfil_publico`, `v_puntaje_postulacion`, `v_ranking_provisional`, `v_proceso_pesos`, `v_bandeja_tareas`, `v_embudo_proceso`.

### Hallazgo durante las pruebas
Una columna generada + trigger `BEFORE INSERT` evalúa la expresión sobre una fila en ceros, causando "Division by 0". Solución: `NULLIF(puntaje_maximo, 0)`.

## 5. Diagrama ER
`er_talento_uagrm.png` / `.svg`, generado desde la base real. Las 73 FK están dibujadas:
- Azul grueso con pata de gallo y nombre de FK: relaciones de negocio.
- Naranja punteado: 13 FK de auditoría hacia `usuario` (`creado_por`, `validado_por`, etc.).

## 6. Otras respuestas dadas
- **N:M rol–usuario:** se justificó originalmente por si alguien tuviera dos roles, pero Cristian prefirió un solo rol; se simplificó.
- **Endpoints:** se estimó el número según los módulos del modelo; la lista MVP queda pendiente de armar.
- **Modelo de negocio:** se explicó cómo se interpretó, para que Cristian verificara si coincide.

## 7. Riesgos y mejoras señaladas
- Validar con el cliente la escala y los mínimos por test.
- Protección de datos personales: consentimiento registrado, acceso restringido a datos sensibles.
- Evitar filtrar respuestas correctas de los tests al front.
- Definir política de retención de archivos y datos.
- Revisión por un ingeniero de software antes de producción.

## 8. Entregables
- `talento_uagrm.sql` — script completo de la BD actualizada
- `er_talento_uagrm.png` / `.svg` — diagrama ER
- Documento en Docs con la explicación del diseño

## 9. Pendientes sugeridos
- Lista de endpoints del MVP
- Guion para presentar el boceto y el modelo
