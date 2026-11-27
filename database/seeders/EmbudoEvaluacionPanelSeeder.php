<?php

namespace Database\Seeders;

use App\Enums\DepartamentoCi;
use Database\Seeders\Concerns\SiembraCandidatosEmpresa;
use Database\Seeders\Concerns\SiembraPostulacionEgresado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * Datos demo para el panel del evaluador:
 * - Egresados con tests aprobados: CV pendiente (fase 6) y entrevista pendiente (fase 7).
 * - Empresas con candidatos externos: tests OK y entrevista pendiente (fase 4).
 * - Casos descartados por bajo desempeño en psicométricos.
 *
 * Ejecutar después de DemoSeeder. Claves: password123 (egresados) / externo123 (empresa).
 */
class EmbudoEvaluacionPanelSeeder extends Seeder
{
    use SiembraCandidatosEmpresa;
    use SiembraPostulacionEgresado;

    public function run(): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('EmbudoEvaluacionPanelSeeder no se ejecuta en producción.');
        }

        $this->command?->info('Sembrando embudo de evaluación (egresados + empresas)…');

        $this->seedCandidatosEgresados(self::egresadosEmbudo());
        $this->seedSolicitudesEmpresa(self::solicitudesEmpresaEmbudo());

        $this->command?->info("\nListo.");
        $this->command?->info('Egresados: cv.panel@ / entrevista.panel@ / rechazo.psico@ → password123');
        $this->command?->info('Empresa: empresa.embudo@talento.test / Empresa1234 · externos *.embudo@ → externo123');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function egresadosEmbudo(): array
    {
        return [
            [
                'meta_fase' => 6,
                'correo' => 'cv.panel@talento.test',
                'password' => 'password123',
                'nombres' => 'Natalia',
                'apellido_paterno' => 'Céspedes',
                'apellido_materno' => 'Roca',
                'ci_numero' => '8411223',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-04-08',
                'telefono' => '74455667',
                'registro_universitario' => '219144556',
                'anio_egreso' => 2025,
                'ppa' => '92.40',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'calidad_psico' => 0.96,
                'likert_patron' => [5, 5, 5, 4, 5],
            ],
            [
                'meta_fase' => 6,
                'correo' => 'cv.panel2@talento.test',
                'password' => 'password123',
                'nombres' => 'Óscar',
                'apellido_paterno' => 'Villarpando',
                'apellido_materno' => 'Choque',
                'ci_numero' => '8422334',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2000-09-12',
                'telefono' => '75566778',
                'registro_universitario' => '219155667',
                'anio_egreso' => 2025,
                'ppa' => '90.10',
                'carrera_like' => '%Redes y Telecomunicaciones%',
                'calidad_psico' => 0.94,
                'likert_patron' => [5, 4, 5, 5, 4],
            ],
            [
                'meta_fase' => 7,
                'correo' => 'entrevista.panel@talento.test',
                'password' => 'password123',
                'nombres' => 'Patricia',
                'apellido_paterno' => 'Montero',
                'apellido_materno' => 'Sandoval',
                'ci_numero' => '8533445',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-01-20',
                'telefono' => '76677889',
                'registro_universitario' => '219166778',
                'anio_egreso' => 2025,
                'ppa' => '93.70',
                'carrera_like' => '%Ingeniería Informática%',
                'calidad_psico' => 0.95,
                'likert_patron' => [5, 5, 4, 5, 5],
                'nota_cv_criterio' => 9.2,
            ],
            [
                'meta_fase' => 7,
                'correo' => 'entrevista.panel2@talento.test',
                'password' => 'password123',
                'nombres' => 'Raúl',
                'apellido_paterno' => 'Ibáñez',
                'apellido_materno' => 'Guerrero',
                'ci_numero' => '8644556',
                'ci_expedido' => DepartamentoCi::LaPaz,
                'fecha_nacimiento' => '1999-11-05',
                'telefono' => '77788990',
                'registro_universitario' => '219177889',
                'anio_egreso' => 2025,
                'ppa' => '91.80',
                'carrera_like' => '%Ingeniería Civil%',
                'calidad_psico' => 0.94,
                'likert_patron' => [4, 5, 5, 5, 4],
                'nota_cv_criterio' => 8.8,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'rechazo.psico@talento.test',
                'password' => 'password123',
                'nombres' => 'Simón',
                'apellido_paterno' => 'Bustillos',
                'apellido_materno' => 'Nina',
                'ci_numero' => '8755667',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-06-30',
                'telefono' => '78899001',
                'registro_universitario' => '219188990',
                'anio_egreso' => 2025,
                'ppa' => '84.00',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'calidad_psico' => 0.32,
                'likert_patron' => [2, 2, 3, 2, 2],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function solicitudesEmpresaEmbudo(): array
    {
        return [
            [
                'razon_social' => 'Lab Embudo Tech S.R.L.',
                'nit' => '900200101',
                'correo_empresa' => 'rrhh@labembudo.test',
                'correo_contacto' => 'empresa.embudo@talento.test',
                'telefono' => '74500101',
                'contacto_nombres' => 'Gabriela',
                'contacto_apellido_paterno' => 'Fernández',
                'contacto_apellido_materno' => 'Lino',
                'contacto_ci' => '7020101',
                'contacto_ci_expedido' => DepartamentoCi::SantaCruz,
                'contacto_fecha_nacimiento' => '1987-05-18',
                'cargo_puesto' => 'Analista de software',
                'area' => 'Desarrollo',
                'descripcion_funciones' => 'Soporte a plataformas internas y QA.',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'perfil_ideal_default' => 7,
                'candidatos' => [
                    [
                        'meta_fase' => 4,
                        'nombres' => 'Teresa',
                        'apellido_paterno' => 'Avalos',
                        'apellido_materno' => 'Méndez',
                        'ci_numero' => '9040101',
                        'fecha_nacimiento' => '1998-08-14',
                        'correo' => 'externo.embudo.entrevista@talento.test',
                        'calidad_psico' => 0.96,
                    ],
                    [
                        'meta_fase' => 4,
                        'nombres' => 'Ulises',
                        'apellido_paterno' => 'Camacho',
                        'ci_numero' => '9040102',
                        'fecha_nacimiento' => '1997-12-02',
                        'correo' => 'externo.embudo.entrevista2@talento.test',
                        'calidad_psico' => 0.94,
                    ],
                    [
                        'meta_fase' => 3,
                        'nombres' => 'Valentina',
                        'apellido_paterno' => 'Díaz',
                        'ci_numero' => '9040103',
                        'fecha_nacimiento' => '1999-03-27',
                        'correo' => 'externo.embudo.psico@talento.test',
                        'calidad_psico' => 0.96,
                    ],
                    [
                        'meta_fase' => 5,
                        'nombres' => 'Walter',
                        'apellido_paterno' => 'Espinoza',
                        'ci_numero' => '9040104',
                        'fecha_nacimiento' => '1996-07-09',
                        'correo' => 'externo.embudo.bajo@talento.test',
                        'calidad_psico' => 0.35,
                    ],
                ],
            ],
            [
                'razon_social' => 'Servicios Andina Express',
                'nit' => '900200102',
                'correo_empresa' => 'talento@andina-express.test',
                'correo_contacto' => 'empresa.andina.embudo@talento.test',
                'contacto_nombres' => 'Miguel',
                'contacto_apellido_paterno' => 'Agreda',
                'contacto_ci' => '7020102',
                'contacto_fecha_nacimiento' => '1984-10-22',
                'cargo_puesto' => 'Ingeniero de soporte',
                'carrera_like' => '%Redes y Telecomunicaciones%',
                'descripcion_funciones' => 'Mesa de ayuda y redes WAN.',
                'candidatos' => [
                    [
                        'meta_fase' => 4,
                        'nombres' => 'Ximena',
                        'apellido_paterno' => 'Guzmán',
                        'apellido_materno' => 'Poma',
                        'ci_numero' => '9040201',
                        'fecha_nacimiento' => '1995-02-11',
                        'correo' => 'externo.andina.entrevista@talento.test',
                        'calidad_psico' => 0.96,
                    ],
                ],
            ],
        ];
    }
}
