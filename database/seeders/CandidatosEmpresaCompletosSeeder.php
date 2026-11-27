<?php

namespace Database\Seeders;

use App\Enums\DepartamentoCi;
use Database\Seeders\Concerns\SiembraCandidatosEmpresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * Empresas con solicitud aprobada y candidatos externos que ya terminaron
 * batería psicológica + entrevista (flujo EMPRESA, sin PPA ni CV).
 */
class CandidatosEmpresaCompletosSeeder extends Seeder
{
    use SiembraCandidatosEmpresa;

    public function run(): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('CandidatosEmpresaCompletosSeeder no se ejecuta en producción.');
        }

        $this->command?->info('Generando solicitudes de empresa con candidatos completos…');

        $this->seedSolicitudesEmpresaCompletas(self::solicitudes());

        $this->command?->info("\nListo. Candidatos externos: externo123 · Contactos empresa: Empresa1234");
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function solicitudes(): array
    {
        return [
            [
                'razon_social' => 'Norte Digital S.R.L.',
                'nit' => '900100001',
                'correo_empresa' => 'rrhh@nortedigital.test',
                'correo_contacto' => 'empresa.norte@talento.test',
                'telefono' => '73111001',
                'contacto_nombres' => 'Laura',
                'contacto_apellido_paterno' => 'Méndez',
                'contacto_apellido_materno' => 'Roca',
                'contacto_ci' => '7011001',
                'contacto_ci_expedido' => DepartamentoCi::SantaCruz,
                'contacto_fecha_nacimiento' => '1988-03-15',
                'cargo_puesto' => 'Desarrollador full stack',
                'area' => 'Tecnología',
                'descripcion_funciones' => 'Mantenimiento de APIs y frontend React.',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'perfil_ideal_default' => 7,
                'candidatos' => [
                    [
                        'nombres' => 'Diego',
                        'apellido_paterno' => 'Rojas',
                        'apellido_materno' => 'Vega',
                        'ci_numero' => '9011001',
                        'fecha_nacimiento' => '1999-06-20',
                        'correo' => 'externo.norte.top@talento.test',
                        'calidad_psico' => 1.0,
                        'nota_entrevista' => 9.8,
                    ],
                    [
                        'nombres' => 'María',
                        'apellido_paterno' => 'Salazar',
                        'apellido_materno' => 'López',
                        'ci_numero' => '9011002',
                        'fecha_nacimiento' => '2000-02-11',
                        'correo' => 'externo.norte.medio@talento.test',
                        'calidad_psico' => 0.8,
                        'nota_entrevista' => 8.6,
                    ],
                    [
                        'nombres' => 'Pablo',
                        'apellido_paterno' => 'Arce',
                        'ci_numero' => '9011003',
                        'fecha_nacimiento' => '1998-11-02',
                        'correo' => 'externo.norte.base@talento.test',
                        'calidad_psico' => 0.65,
                        'nota_entrevista' => 7.5,
                    ],
                ],
            ],
            [
                'razon_social' => 'Grupo Andino Retail',
                'nit' => '900100002',
                'correo_empresa' => 'seleccion@andino-retail.test',
                'correo_contacto' => 'empresa.andino@talento.test',
                'telefono' => '73111002',
                'contacto_nombres' => 'Roberto',
                'contacto_apellido_paterno' => 'Quiroga',
                'contacto_ci' => '7011002',
                'contacto_ci_expedido' => DepartamentoCi::LaPaz,
                'contacto_fecha_nacimiento' => '1985-09-08',
                'cargo_puesto' => 'Analista de datos',
                'area' => 'BI y analítica',
                'descripcion_funciones' => 'Dashboards y ETL para ventas regionales.',
                'carrera_like' => '%Ingeniería Informática%',
                'perfil_ideal_default' => 6,
                'candidatos' => [
                    [
                        'nombres' => 'Sandra',
                        'apellido_paterno' => 'Illanes',
                        'apellido_materno' => 'Choque',
                        'ci_numero' => '9021001',
                        'fecha_nacimiento' => '1997-04-18',
                        'correo' => 'externo.andino.top@talento.test',
                        'calidad_psico' => 0.95,
                        'nota_entrevista' => 9.5,
                    ],
                    [
                        'nombres' => 'Tomás',
                        'apellido_paterno' => 'Fernández',
                        'ci_numero' => '9021002',
                        'fecha_nacimiento' => '1996-08-25',
                        'correo' => 'externo.andino.medio@talento.test',
                        'calidad_psico' => 0.72,
                        'nota_entrevista' => 8.0,
                    ],
                ],
            ],
            [
                'razon_social' => 'Conecta Telecom Bolivia',
                'nit' => '900100003',
                'correo_empresa' => 'talento@conecta.test',
                'correo_contacto' => 'empresa.conecta@talento.test',
                'contacto_nombres' => 'Valeria',
                'contacto_apellido_paterno' => 'Suárez',
                'contacto_ci' => '7011003',
                'contacto_fecha_nacimiento' => '1991-12-01',
                'cargo_puesto' => 'Ingeniero de redes',
                'carrera_like' => '%Redes y Telecomunicaciones%',
                'descripcion_funciones' => 'Despliegue y monitoreo de infraestructura.',
                'candidatos' => [
                    [
                        'nombres' => 'Luis',
                        'apellido_paterno' => 'Montenegro',
                        'apellido_materno' => 'Paz',
                        'ci_numero' => '9031001',
                        'fecha_nacimiento' => '1995-01-30',
                        'correo' => 'externo.conecta.top@talento.test',
                        'calidad_psico' => 0.9,
                        'nota_entrevista' => 9.2,
                    ],
                ],
            ],
        ];
    }
}