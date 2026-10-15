<?php

namespace Database\Seeders;

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
                ['codigo' => 'ADMIN', 'nombre' => 'Administrador de la consultora', 'descripcion' => 'Configura procesos, pruebas, ranking y solicitudes'],
                ['codigo' => 'AYUDANTE', 'nombre' => 'Ayudante / junior de carrera', 'descripcion' => 'Revisa tareas manuales: CV, entrevista y antecedentes'],
                ['codigo' => 'EGRESADO', 'nombre' => 'Egresado UAGRM', 'descripcion' => 'Postula al talento universitario'],
                ['codigo' => 'CANDIDATO_EXTERNO', 'nombre' => 'Candidato de empresa', 'descripcion' => 'Persona invitada por una empresa cliente'],
                ['codigo' => 'EMPRESA', 'nombre' => 'Empresa cliente', 'descripcion' => 'Solicita evaluaciones y recibe informes'],
            ], ['codigo'], ['nombre', 'descripcion']);

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

            $dimensiones = [
                'SOCIABILIDAD' => 'Sociabilidad', 'CAPACIDAD_ESCOLAR' => 'Capacidad escolar',
                'ESTABILIDAD_EMOCIONAL' => 'Estabilidad emocional', 'SUMISION' => 'Sumisión',
                'RETRAIMIENTO' => 'Retraimiento', 'SUPEREGO' => 'Superego', 'TIMIDEZ' => 'Timidez',
                'SEVERIDAD' => 'Severidad', 'CONFIANZA' => 'Confianza', 'OBJETIVIDAD' => 'Objetividad',
                'INGENUIDAD' => 'Ingenuidad', 'SEGURIDAD_DE_SI_MISMO' => 'Seguridad de sí mismo',
                'CONSERVADURISMO' => 'Conservadurismo', 'DEPENDENCIA_GRUPAL' => 'Dependencia grupal',
                'INDIFERENCIA' => 'Indiferencia', 'TRANQUILIDAD' => 'Tranquilidad',
            ];
            $orden = 1;
            DB::table('dimension_evaluada')->upsert(
                collect($dimensiones)->map(fn (string $nombre, string $codigo): array => [
                    'codigo' => $codigo, 'nombre' => $nombre, 'tipo' => 'PERSONALIDAD', 'orden' => $orden++,
                ])->values()->all(),
                ['codigo'],
                ['nombre', 'tipo', 'orden'],
            );

            Prueba::query()->upsert([
                ['codigo' => 'PPA', 'nombre' => 'Promedio ponderado histórico', 'tipo' => 'CALCULADA', 'descripcion' => 'Filtro del flujo A: compara el PPA con el mínimo de la facultad'],
                ['codigo' => 'CV_ANALISIS', 'nombre' => 'Análisis de currículum', 'tipo' => 'RUBRICA', 'descripcion' => 'Revisión manual con rúbrica'],
                ['codigo' => 'TEST_PERSONALIDAD', 'nombre' => 'Test de personalidad', 'tipo' => 'TEST', 'descripcion' => 'Obligatorio; genera el gráfico de perfil'],
                ['codigo' => 'TEST_INTELIGENCIA', 'nombre' => 'Test de inteligencia', 'tipo' => 'TEST', 'descripcion' => 'Obligatorio; matrices de figuras'],
                ['codigo' => 'TEST_INTELIGENCIA_EMOCIONAL', 'nombre' => 'Test de inteligencia emocional', 'tipo' => 'TEST', 'descripcion' => 'Obligatorio'],
                ['codigo' => 'TEST_LECTURA_COMPRENSIVA', 'nombre' => 'Test de lectura comprensiva', 'tipo' => 'TEST', 'descripcion' => null],
                ['codigo' => 'TEST_APTITUD_NUMERICA', 'nombre' => 'Test de aptitud numérica', 'tipo' => 'TEST', 'descripcion' => null],
                ['codigo' => 'TEST_HABILIDADES_BLANDAS', 'nombre' => 'Test de habilidades blandas', 'tipo' => 'TEST', 'descripcion' => 'Genera el gráfico de habilidades'],
                ['codigo' => 'ENTREVISTA', 'nombre' => 'Entrevista', 'tipo' => 'RUBRICA', 'descripcion' => 'Formulario completado por el junior de la carrera'],
            ], ['codigo'], ['nombre', 'tipo', 'descripcion']);

            $pruebaCv = Prueba::query()->where('codigo', 'CV_ANALISIS')->valueOrFail('id');
            DB::table('rubrica_criterio')->upsert(
                collect(['Formación', 'Experiencia', 'Habilidades', 'Presentación'])
                    ->map(fn (string $nombre, int $indice): array => [
                        'prueba_id' => $pruebaCv, 'nombre' => $nombre, 'puntaje_maximo' => '10.00', 'orden' => $indice + 1,
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
