<?php

namespace Database\Seeders;

use App\Catalogos\DimensionesInforme;
use App\Catalogos\LibrosPrueba;
use App\Models\Etapa;
use App\Models\ParametroSistema;
use App\Models\Prueba;
use App\Models\Rol;
use App\Models\TipoDocumento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos fijos del sistema. Idempotente: se puede ejecutar en producción tantas veces como haga falta.
 *
 * php artisan db:seed --class=CatalogoSeeder --force
 */
class CatalogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Rol::query()->upsert([
                ['codigo' => 'ADMIN', 'nombre' => 'Administrador de la consultora', 'descripcion' => 'Configura procesos, pruebas, ranking y solicitudes', 'es_panel' => true, 'alcance_carrera' => false],
                ['codigo' => 'AYUDANTE', 'nombre' => 'Ayudante / junior de carrera', 'descripcion' => 'Revisa tareas manuales: CV, entrevista y antecedentes', 'es_panel' => true, 'alcance_carrera' => true],
                ['codigo' => 'EGRESADO', 'nombre' => 'Egresado UAGRM', 'descripcion' => 'Postula al talento universitario', 'es_panel' => false, 'alcance_carrera' => false],
                ['codigo' => 'CANDIDATO_EXTERNO', 'nombre' => 'Candidato de empresa', 'descripcion' => 'Persona invitada por una empresa cliente', 'es_panel' => false, 'alcance_carrera' => false],
                ['codigo' => 'EMPRESA', 'nombre' => 'Empresa cliente', 'descripcion' => 'Solicita evaluaciones y recibe informes', 'es_panel' => false, 'alcance_carrera' => false],
            ], ['codigo'], ['nombre', 'descripcion', 'es_panel', 'alcance_carrera']);

            Etapa::query()->upsert([
                ['codigo' => 'INSCRIPCION', 'nombre' => 'Inscripción', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'Registro del postulante'],
                ['codigo' => 'REQUISITOS', 'nombre' => 'Requisitos', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'Documentos obligatorios y PPA mínimo'],
                ['codigo' => 'ANALISIS_CV', 'nombre' => 'Análisis de CV', 'ejecucion' => 'MANUAL', 'descripcion' => 'Revisión del currículum con rúbrica'],
                ['codigo' => 'EVALUACION_TECNICA', 'nombre' => 'Evaluación técnica', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'PPA (flujo A) o prueba técnica de las funciones (flujo B)'],
                ['codigo' => 'EVALUACION_PSICOLOGICA', 'nombre' => 'Evaluación psicológica', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'Batería de tests en línea'],
                ['codigo' => 'HABILIDADES_BLANDAS', 'nombre' => 'Medición de habilidades blandas', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'Test o formulario de habilidades blandas'],
                ['codigo' => 'ENTREVISTA', 'nombre' => 'Entrevista', 'ejecucion' => 'MANUAL', 'descripcion' => 'Entrevista con formulario'],
                ['codigo' => 'INVESTIGACION_ANTECEDENTES', 'nombre' => 'Investigación de antecedentes', 'ejecucion' => 'MANUAL', 'descripcion' => 'Verificación de empleos con referencias'],
                ['codigo' => 'SELECCION', 'nombre' => 'Selección', 'ejecucion' => 'AUTOMATICA', 'descripcion' => 'Ranking y selección de terna o quinteto'],
                ['codigo' => 'INFORME', 'nombre' => 'Informe y recomendaciones', 'ejecucion' => 'MANUAL', 'descripcion' => 'Informe final a la empresa'],
            ], ['codigo'], ['nombre', 'ejecucion', 'descripcion']);

            DB::table('dimension_evaluada')->upsert(
                collect(DimensionesInforme::factores16Pf())->map(fn (array $factor): array => [
                    'codigo' => $factor['codigo'],
                    'factor' => $factor['factor'],
                    'nombre' => $factor['nombre'],
                    'polo_bajo' => $factor['polo_bajo'],
                    'polo_alto' => $factor['polo_alto'],
                    'tipo' => 'PERSONALIDAD',
                    'orden' => $factor['orden'],
                ])->all(),
                ['codigo'],
                ['factor', 'nombre', 'polo_bajo', 'polo_alto', 'tipo', 'orden'],
            );

            DB::table('dimension_evaluada')->upsert(
                collect(DimensionesInforme::habilidadesBlandas())->map(fn (array $dimension): array => [
                    'codigo' => $dimension['codigo'],
                    'nombre' => $dimension['nombre'],
                    'tipo' => 'HABILIDAD_BLANDA',
                    'orden' => $dimension['orden'],
                ])->all(),
                ['codigo'],
                ['nombre', 'tipo', 'orden'],
            );

            Prueba::query()->upsert([
                ['codigo' => 'PPA', 'nombre' => 'Promedio ponderado histórico', 'tipo' => 'CALCULADA', 'familia' => null, 'descripcion' => 'Filtro del flujo A: compara el PPA con el mínimo de la facultad', 'limite_preguntas' => null, 'tiempo_limite_seg' => null],
                ['codigo' => 'CV_ANALISIS', 'nombre' => 'Análisis de currículum', 'tipo' => 'RUBRICA', 'familia' => null, 'descripcion' => 'Revisión manual con rúbrica', 'limite_preguntas' => null, 'tiempo_limite_seg' => null],
                ['codigo' => 'ENTREVISTA', 'nombre' => 'Entrevista', 'tipo' => 'RUBRICA', 'familia' => null, 'descripcion' => 'Formulario completado por el junior de la carrera', 'limite_preguntas' => null, 'tiempo_limite_seg' => null],
            ], ['codigo'], ['nombre', 'tipo', 'familia', 'descripcion', 'limite_preguntas', 'tiempo_limite_seg']);

            foreach (LibrosPrueba::definiciones() as $libro) {
                Prueba::query()->updateOrCreate(
                    ['codigo' => $libro['codigo']],
                    [
                        'nombre' => $libro['nombre'],
                        'tipo' => 'TEST',
                        'familia' => $libro['familia'],
                        'descripcion' => $libro['descripcion'],
                        'es_plantilla' => true,
                        'activa' => true,
                        'limite_preguntas' => null,
                        'tiempo_limite_seg' => $libro['tiempo_limite_seg'],
                    ],
                );
            }

            Prueba::query()
                ->whereIn('codigo', LibrosPrueba::codigosRetiradosDelBanco())
                ->update([
                    'activa' => false,
                    'es_plantilla' => false,
                ]);

            $pruebaCv = Prueba::query()->where('codigo', 'CV_ANALISIS')->valueOrFail('id');
            DB::table('rubrica_criterio')->upsert(
                collect(['Formación', 'Experiencia', 'Habilidades', 'Presentación'])
                    ->map(fn (string $nombre, int $indice): array => [
                        'prueba_id' => $pruebaCv, 'nombre' => $nombre, 'puntaje_maximo' => '10.00', 'orden' => $indice + 1,
                    ])->all(),
                ['prueba_id', 'nombre'],
                ['puntaje_maximo', 'orden'],
            );

            $pruebaEntrevista = Prueba::query()->where('codigo', 'ENTREVISTA')->valueOrFail('id');
            DB::table('rubrica_criterio')->upsert(
                collect(['Presencia', 'Vocabulario', 'Seguridad', 'Conocimientos técnicos'])
                    ->map(fn (string $nombre, int $indice): array => [
                        'prueba_id' => $pruebaEntrevista, 'nombre' => $nombre, 'puntaje_maximo' => '10.00', 'orden' => $indice + 1,
                    ])->all(),
                ['prueba_id', 'nombre'],
                ['puntaje_maximo', 'orden'],
            );

            TipoDocumento::query()->upsert([
                ['codigo' => 'HISTORICO_ACADEMICO', 'nombre' => 'Histórico académico'],
                ['codigo' => 'CEDULA_IDENTIDAD', 'nombre' => 'Cédula de identidad'],
                ['codigo' => 'FOTOGRAFIA', 'nombre' => 'Fotografía'],
                ['codigo' => 'CURRICULUM_VITAE', 'nombre' => 'Currículum vitae'],
            ], ['codigo'], ['nombre']);

            // Los valores de los parámetros los edita el administrador: solo se insertan si faltan
            ParametroSistema::query()->insertOrIgnore([
                ['clave' => 'top_n_defecto', 'valor' => '5', 'descripcion' => 'Cantidad de candidatos del top al crear un proceso'],
                ['clave' => ParametroSistema::VERSION_POLITICA_PRIVACIDAD, 'valor' => '1.0', 'descripcion' => 'Versión vigente de la política de tratamiento de datos'],
                ['clave' => 'dias_alerta_tarea_manual', 'valor' => '5', 'descripcion' => 'Días tras los cuales una tarea manual se marca atrasada'],
            ]);
        });
    }
}
