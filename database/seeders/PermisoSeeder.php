<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo estándar de permisos del sistema y asignación inicial a roles (RBAC).
 */
class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Módulo Académico
            [
                'codigo' => 'facultades.gestionar',
                'nombre' => 'Gestionar Facultades',
                'modulo' => 'ACADEMICO',
                'descripcion' => 'Crear, editar, activar y eliminar facultades y definir PPA mínimo.',
            ],
            [
                'codigo' => 'carreras.gestionar',
                'nombre' => 'Gestionar Carreras',
                'modulo' => 'ACADEMICO',
                'descripcion' => 'Crear, editar, activar y eliminar carreras universitarias.',
            ],
            [
                'codigo' => 'convocatorias.gestionar',
                'nombre' => 'Gestionar Convocatorias',
                'modulo' => 'ACADEMICO',
                'descripcion' => 'Crear, configurar, abrir, pausar o cerrar convocatorias de talento.',
            ],

            // Módulo Evaluaciones y Postulaciones
            [
                'codigo' => 'postulaciones.ver',
                'nombre' => 'Ver Expedientes de Postulación',
                'modulo' => 'EVALUACIONES',
                'descripcion' => 'Consultar los expedientes, avances y documentos de los postulantes.',
            ],
            [
                'codigo' => 'requisitos.aprobar',
                'nombre' => 'Validar y Aprobar Requisitos',
                'modulo' => 'EVALUACIONES',
                'descripcion' => 'Revisar y validar los documentos de la Etapa 3 para habilitar tests.',
            ],
            [
                'codigo' => 'cv.calificar',
                'nombre' => 'Calificar Rúbrica de CV',
                'modulo' => 'EVALUACIONES',
                'descripcion' => 'Evaluar con la rúbrica institucional de 40 pts el currículum de la Etapa 6.',
            ],
            [
                'codigo' => 'entrevista.calificar',
                'nombre' => 'Calificar Rúbrica de Entrevista',
                'modulo' => 'EVALUACIONES',
                'descripcion' => 'Evaluar oralmente con la rúbrica institucional de 40 pts la Etapa 7.',
            ],
            [
                'codigo' => 'ranking.adjudicar',
                'nombre' => 'Adjudicar y Publicar Ranking',
                'modulo' => 'EVALUACIONES',
                'descripcion' => 'Calcular ranking oficial, adjudicar plazas y publicar perfiles en la landing.',
            ],

            // Módulo Usuarios y Seguridad
            [
                'codigo' => 'usuarios.ver',
                'nombre' => 'Ver Listado de Usuarios',
                'modulo' => 'USUARIOS',
                'descripcion' => 'Consultar usuarios, estados y roles en el panel de administración.',
            ],
            [
                'codigo' => 'usuarios.gestionar',
                'nombre' => 'Gestionar Usuarios y Cuentas',
                'modulo' => 'USUARIOS',
                'descripcion' => 'Crear cuentas de evaluadores/ayudantes, cambiar roles y suspender/activar.',
            ],
            [
                'codigo' => 'permisos.gestionar',
                'nombre' => 'Administrar Roles y Permisos',
                'modulo' => 'USUARIOS',
                'descripcion' => 'Modificar la matriz de permisos asociados a cada rol del sistema.',
            ],
        ];

        DB::transaction(function () use ($permisos) {
            foreach ($permisos as $p) {
                Permiso::updateOrCreate(
                    ['codigo' => $p['codigo']],
                    [
                        'nombre' => $p['nombre'],
                        'modulo' => $p['modulo'],
                        'descripcion' => $p['descripcion'],
                    ]
                );
            }

            // Asignar permisos a roles
            $adminRol = Rol::where('codigo', 'ADMIN')->first();
            $ayudanteRol = Rol::where('codigo', 'AYUDANTE')->first();

            if ($adminRol) {
                // Admin tiene todos los permisos
                $todosIds = Permiso::pluck('id')->all();
                $adminRol->permisos()->sync($todosIds);
            }

            if ($ayudanteRol) {
                // Ayudante tiene permisos de revisión y calificación en el flujo
                $ayudanteCodigos = [
                    'postulaciones.ver',
                    'requisitos.aprobar',
                    'cv.calificar',
                    'entrevista.calificar',
                ];
                $ayudanteIds = Permiso::whereIn('codigo', $ayudanteCodigos)->pluck('id')->all();
                $ayudanteRol->permisos()->sync($ayudanteIds);
            }
        });
    }
}

