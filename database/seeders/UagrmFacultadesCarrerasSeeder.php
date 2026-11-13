<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder completo de Facultades y Carreras oficiales de la
 * Universidad Autónoma Gabriel René Moreno (UAGRM) - Santa Cruz, Bolivia.
 *
 * Idempotente: se puede ejecutar repetidamente sin duplicar registros.
 */
class UagrmFacultadesCarrerasSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            [
                'nombre' => 'Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones',
                'sigla' => 'FICCT',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Informática' => ['Desarrollo de Software', 'Inteligencia Artificial', 'Ciberseguridad'],
                    'Ingeniería de Sistemas' => ['Gestión de TI', 'Bases de Datos', 'Arquitectura de Software'],
                    'Ingeniería en Redes y Telecomunicaciones' => ['Infraestructura y Redes', 'Telecomunicaciones', 'IoT'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Exactas y Tecnología',
                'sigla' => 'FCET',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Civil' => ['Estructuras', 'Hidráulica', 'Vías de Comunicación'],
                    'Ingeniería Industrial' => ['Gestión de Producción', 'Seguridad y Salud Ocupacional', 'Logística'],
                    'Ingeniería Química' => ['Procesos Químicos', 'Biotecnología', 'Medio Ambiente'],
                    'Ingeniería Electromecánica' => ['Automatización Industrial', 'Generación de Energía', 'Mantenimiento'],
                    'Ingeniería Mecánica' => ['Diseño Mecánico', 'Termofluidos', 'Manufactura'],
                    'Ingeniería Petrolera' => ['Perforación y Producción', 'Reservorios', 'Refinación'],
                    'Ingeniería en Control de Procesos' => ['Instrumentación', 'Sistemas de Control'],
                    'Ingeniería de Alimentos' => ['Control de Calidad', 'Tecnología de Procesamiento'],
                    'Ingeniería Ambiental' => ['Gestión Ambiental', 'Tratamiento de Aguas', 'Evaluación de Impacto'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Económicas, Administrativas y Financieras',
                'sigla' => 'FCEAF',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Administración de Empresas' => ['Finanzas', 'Talento Humano', 'Marketing'],
                    'Contaduría Pública' => ['Auditoría Financiera', 'Tributación', 'Costos'],
                    'Ingeniería Comercial' => ['Marketing Estratégico', 'Negociación y Ventas'],
                    'Ingeniería Financiera' => ['Mercado de Valores', 'Riesgos Financieros', 'Banca'],
                    'Comercio Internacional' => ['Aduanas y Logística', 'Negocios Globales'],
                    'Economía' => ['Microeconomía', 'Macroeconomía y Políticas Públicas'],
                    'Información y Control de Gestión' => ['Auditoría de Sistemas', 'Control de Gestión'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Jurídicas, Políticas, Sociales y Relaciones Internacionales',
                'sigla' => 'FCJPS',
                'ppa_minimo' => 60.00,
                'carreras' => [
                    'Derecho' => ['Derecho Penal', 'Derecho Civil', 'Derecho Laboral', 'Derecho Corporativo'],
                    'Ciencias Políticas y Administración Pública' => ['Gestión Pública', 'Políticas Públicas'],
                    'Relaciones Internacionales' => ['Diplomacia', 'Comercio Exterior'],
                    'Trabajo Social' => ['Bienestar Social', 'Proyectos Comunitarios'],
                    'Sociología' => ['Investigación Social', 'Estudios de Opinión'],
                    'Ciencias de la Comunicación' => ['Periodismo', 'Comunicación Corporativa', 'Medios Digitales'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias de la Salud Humana',
                'sigla' => 'FCSH',
                'ppa_minimo' => 55.00,
                'carreras' => [
                    'Medicina' => ['Medicina General', 'Salud Pública'],
                    'Enfermería' => ['Enfermería Clínica', 'Atención Comunitaria'],
                    'Odontología' => ['Ortodoncia', 'Cirugía Bucal'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Farmacéuticas y Bioquímicas',
                'sigla' => 'FCFB',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Farmacia' => ['Farmacología Clínica', 'Farmacia Industrial'],
                    'Bioquímica' => ['Análisis Clínicos', 'Biotecnología'],
                ],
            ],
            [
                'nombre' => 'Facultad de Humanidades',
                'sigla' => 'FH',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Psicología' => ['Psicología Organizacional', 'Psicología Clínica', 'Psicología Educativa'],
                    'Ciencias de la Educación' => ['Tecnología Educativa', 'Diseño Curricular'],
                    'Lenguas Modernas y Filología Inglesa' => ['Traducción e Interpretación', 'Enseñanza del Idioma'],
                    'Gestión del Turismo' => ['Turismo Sostenible', 'Administración Hotelera'],
                    'Actividad Física' => ['Entrenamiento Deportivo', 'Salud y Recreación'],
                    'Filosofía' => ['Ética', 'Epistemología'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Agrícolas',
                'sigla' => 'FCA',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Agronómica' => ['Producción Agrícola', 'Fitotecnia', 'Riegos'],
                    'Ingeniería Forestal' => ['Manejo de Bosques', 'Industria Maderera'],
                    'Ingeniería Agrícola' => ['Maquinaria Agrícola', 'Sistemas de Riego'],
                    'Biología' => ['Biodiversidad', 'Ecología y Conservación'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias Veterinarias',
                'sigla' => 'FCV',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Medicina Veterinaria y Zootecnia' => ['Clínica de Menores', 'Producción de Ganado Mayor', 'Sanidad Animal'],
                ],
            ],
            [
                'nombre' => 'Facultad de Ciencias del Hábitat, Diseño y Arte',
                'sigla' => 'FCHDA',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Arquitectura' => ['Diseño Arquitectónico', 'Urbanismo', 'Construcción'],
                    'Diseño Integral' => ['Diseño de Espacios', 'Diseño de Productos'],
                    'Planificación Territorial' => ['Ordenamiento Territorial', 'Desarrollo Urbano'],
                    'Diseño Gráfico' => ['Branding e Identidad', 'Diseño Digital y UX'],
                ],
            ],
            [
                'nombre' => 'Facultad Politécnica',
                'sigla' => 'FP',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Construcciones Civiles' => ['Supervisión de Obras', 'Topografía'],
                    'Electricidad Industrial' => ['Instalaciones Eléctricas', 'Mantenimiento'],
                    'Electrónica' => ['Electrónica Digital', 'Telecomunicaciones'],
                    'Mecánica General' => ['Mecanizado', 'Soldadura Industrial'],
                    'Química Industrial' => ['Laboratorio Químico', 'Control de Calidad'],
                    'Mecánica Automotriz' => ['Diagnóstico Electrónico', 'Motores'],
                    'Redes y Comunicaciones' => ['Cableado Estructurado', 'Soporte de Redes'],
                    'Ofimática' => ['Sistemas de Información Administrativa'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral del Norte (Montero)',
                'sigla' => 'FINOR',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería de Sistemas (Montero)' => ['Desarrollo de Software'],
                    'Contaduría Pública (Montero)' => ['Auditoría'],
                    'Enfermería (Montero)' => ['Salud Comunitaria'],
                    'Medicina (Montero)' => ['Medicina General'],
                    'Ingeniería del Gas y Petroquímica' => ['Petroquímica'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral del Chaco (Camiri)',
                'sigla' => 'FICH',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería del Petróleo y Gas Natural (Camiri)' => ['Hidrocarburos'],
                    'Contaduría Pública (Camiri)' => ['Finanzas'],
                    'Derecho (Camiri)' => ['Ciencias Jurídicas'],
                    'Enfermería (Camiri)' => ['Atención Primaria'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral de los Valles Cruceños (Vallegrande)',
                'sigla' => 'FIVC',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Agropecuaria' => ['Producción Agropecuaria'],
                    'Pedagogía' => ['Docencia y Orientación'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral Chiquitana (San Ignacio)',
                'sigla' => 'FACH',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Zootecnista' => ['Producción Animal'],
                    'Contaduría Pública (San Ignacio)' => ['Contabilidad y Finanzas'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral del Noreste (San Julián)',
                'sigla' => 'FINE',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Ingeniería Agropecuaria (San Julián)' => ['Cultivos Extensivos'],
                    'Contaduría Pública (San Julián)' => ['Auditoría'],
                ],
            ],
            [
                'nombre' => 'Facultad Integral de Ichilo (Yapacaní)',
                'sigla' => 'FINI',
                'ppa_minimo' => 51.00,
                'carreras' => [
                    'Contaduría Pública (Yapacaní)' => ['Contabilidad'],
                    'Ingeniería Financiera (Yapacaní)' => ['Servicios Financieros'],
                ],
            ],
        ];

        DB::transaction(function () use ($catalogo) {
            $totalFacultades = 0;
            $totalCarreras = 0;
            $totalAreas = 0;

            foreach ($catalogo as $datosFacultad) {
                // Actualizar o crear facultad buscando preferentemente por sigla
                $facultad = Facultad::query()->where('sigla', $datosFacultad['sigla'])->first()
                    ?? Facultad::query()->where('nombre', $datosFacultad['nombre'])->first()
                    ?? new Facultad();

                $facultad->nombre = $datosFacultad['nombre'];
                $facultad->sigla = $datosFacultad['sigla'];
                $facultad->ppa_minimo = $datosFacultad['ppa_minimo'];
                $facultad->activa = true;
                $facultad->save();

                $totalFacultades++;

                foreach ($datosFacultad['carreras'] as $nombreCarrera => $areas) {
                    $carrera = Carrera::query()->firstOrCreate(
                        [
                            'facultad_id' => $facultad->id,
                            'nombre' => $nombreCarrera,
                        ],
                        [
                            'activa' => true,
                        ]
                    );

                    $totalCarreras++;

                    foreach ($areas as $nombreArea) {
                        $carrera->areas()->firstOrCreate([
                            'nombre' => $nombreArea,
                        ]);
                        $totalAreas++;
                    }
                }
            }

            $this->command?->info("✓ Se procesaron {$totalFacultades} facultades, {$totalCarreras} carreras y {$totalAreas} áreas de preferencia de la UAGRM.");
        });
    }
}
