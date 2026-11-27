<?php

namespace Database\Seeders;

use App\Enums\DepartamentoCi;
use Database\Seeders\Concerns\SiembraPostulacionEgresado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * Egresados con todo el embudo cerrado: pruebas, CV, entrevista y selección aprobada.
 * Contraseña común: password123
 */
class CandidatosCompletosSeeder extends Seeder
{
    use SiembraPostulacionEgresado;

    public function run(): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('CandidatosCompletosSeeder no se ejecuta en producción.');
        }

        $this->command?->info('Generando egresados con proceso completo…');

        $this->seedCandidatosEgresados(self::candidatos());

        $this->command?->info("\nListo. Inicia sesión con cualquier correo @talento.test y password123.");
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function candidatos(): array
    {
        return [
            [
                'meta_fase' => 8,
                'correo' => 'completo.top1@talento.test',
                'nombres' => 'Daniela',
                'apellido_paterno' => 'Rios',
                'apellido_materno' => 'Aguilera',
                'ci_numero' => '8567891',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-04-12',
                'telefono' => '75566778',
                'registro_universitario' => '219055667',
                'anio_egreso' => 2025,
                'ppa' => '98.20',
                'calidad_psico' => 1.0,
                'likert_patron' => [5, 5, 5, 5, 5],
                'nota_cv_criterio' => 9.8,
                'nota_entrevista_criterio' => 9.9,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.top2@talento.test',
                'nombres' => 'Eduardo',
                'apellido_paterno' => 'Mendoza',
                'apellido_materno' => 'Vargas',
                'ci_numero' => '8678902',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-08-03',
                'telefono' => '76677889',
                'registro_universitario' => '219066778',
                'anio_egreso' => 2025,
                'ppa' => '96.40',
                'carrera_like' => '%Ingeniería Informática%',
                'calidad_psico' => 0.95,
                'likert_patron' => [5, 5, 4, 5, 5],
                'nota_cv_criterio' => 9.5,
                'nota_entrevista_criterio' => 9.6,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.top3@talento.test',
                'nombres' => 'Fernanda',
                'apellido_paterno' => 'Cuellar',
                'apellido_materno' => 'Sosa',
                'ci_numero' => '8789013',
                'ci_expedido' => DepartamentoCi::LaPaz,
                'fecha_nacimiento' => '2002-01-17',
                'telefono' => '77788990',
                'registro_universitario' => '219077889',
                'anio_egreso' => 2025,
                'ppa' => '94.80',
                'carrera_like' => '%Ingeniería Industrial%',
                'calidad_psico' => 0.88,
                'likert_patron' => [5, 4, 5, 4, 5],
                'nota_cv_criterio' => 9.2,
                'nota_entrevista_criterio' => 9.3,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.medio1@talento.test',
                'nombres' => 'Gabriel',
                'apellido_paterno' => 'Ortiz',
                'apellido_materno' => 'Camacho',
                'ci_numero' => '8890124',
                'ci_expedido' => DepartamentoCi::Cochabamba,
                'fecha_nacimiento' => '2001-05-22',
                'telefono' => '78899001',
                'registro_universitario' => '219088990',
                'anio_egreso' => 2025,
                'ppa' => '88.50',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'calidad_psico' => 0.82,
                'likert_patron' => [4, 4, 5, 4, 4],
                'nota_cv_criterio' => 8.5,
                'nota_entrevista_criterio' => 8.7,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.medio2@talento.test',
                'nombres' => 'Helena',
                'apellido_paterno' => 'Paredes',
                'apellido_materno' => 'Nuñez',
                'ci_numero' => '8901235',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-09-30',
                'telefono' => '79900112',
                'registro_universitario' => '219099001',
                'anio_egreso' => 2025,
                'ppa' => '86.20',
                'carrera_like' => '%Redes y Telecomunicaciones%',
                'calidad_psico' => 0.75,
                'likert_patron' => [4, 3, 4, 4, 3],
                'nota_cv_criterio' => 8.0,
                'nota_entrevista_criterio' => 8.2,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.base@talento.test',
                'nombres' => 'Iván',
                'apellido_paterno' => 'Silva',
                'apellido_materno' => 'Roca',
                'ci_numero' => '9012346',
                'ci_expedido' => DepartamentoCi::Beni,
                'fecha_nacimiento' => '2001-12-05',
                'telefono' => '70011223',
                'registro_universitario' => '219100112',
                'anio_egreso' => 2025,
                'ppa' => '82.00',
                'carrera_like' => '%Electrónica%',
                'calidad_psico' => 0.68,
                'likert_patron' => [3, 4, 3, 4, 3],
                'nota_cv_criterio' => 7.5,
                'nota_entrevista_criterio' => 7.8,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.base2@talento.test',
                'nombres' => 'Juliana',
                'apellido_paterno' => 'Arauz',
                'apellido_materno' => 'Peña',
                'ci_numero' => '9123457',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-06-14',
                'telefono' => '71122334',
                'registro_universitario' => '219111223',
                'anio_egreso' => 2025,
                'ppa' => '79.50',
                'carrera_like' => '%Ingeniería Civil%',
                'calidad_psico' => 0.65,
                'likert_patron' => [3, 3, 4, 3, 3],
                'nota_cv_criterio' => 7.2,
                'nota_entrevista_criterio' => 7.5,
            ],
            [
                'meta_fase' => 8,
                'correo' => 'completo.base3@talento.test',
                'nombres' => 'Kevin',
                'apellido_paterno' => 'Zurita',
                'apellido_materno' => 'Fuentes',
                'ci_numero' => '9234568',
                'ci_expedido' => DepartamentoCi::Tarija,
                'fecha_nacimiento' => '2001-02-28',
                'telefono' => '72233445',
                'registro_universitario' => '219122334',
                'anio_egreso' => 2025,
                'ppa' => '77.80',
                'carrera_like' => '%Ingeniería de Sistemas%',
                'calidad_psico' => 0.62,
                'likert_patron' => [3, 3, 3, 4, 3],
                'nota_cv_criterio' => 7.0,
                'nota_entrevista_criterio' => 7.2,
            ],
        ];
    }
}