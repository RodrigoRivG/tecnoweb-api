<?php

namespace Database\Seeders;

use App\Enums\DepartamentoCi;
use Database\Seeders\Concerns\SiembraPostulacionEgresado;
use Illuminate\Database\Seeder;

class EstudiantesPruebaSeeder extends Seeder
{
    use SiembraPostulacionEgresado;

  /**
   * Candidatos en distintas fases del embudo (5 a 8) sobre Ingeniería de Sistemas.
   */
    public function run(): void
    {
        $this->seedCandidatosEgresados(self::candidatosEmbudoPorFases());
        $this->command?->info("\n¡Usuarios por fase generados (ver fase5…fase8 @talento.test, password123)!");
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function candidatosEmbudoPorFases(): array
    {
        return [
            [
                'meta_fase' => 5,
                'correo' => 'fase5.blandas@talento.test',
                'password' => 'password123',
                'nombres' => 'Alejandro',
                'apellido_paterno' => 'Justiniano',
                'apellido_materno' => 'Paz',
                'ci_numero' => '8123451',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-03-10',
                'telefono' => '71122334',
                'registro_universitario' => '219011223',
                'anio_egreso' => 2025,
                'ppa' => '91.50',
            ],
            [
                'meta_fase' => 6,
                'correo' => 'fase6.cv@talento.test',
                'password' => 'password123',
                'nombres' => 'Beatriz',
                'apellido_paterno' => 'Salguero',
                'apellido_materno' => 'Morales',
                'ci_numero' => '8234562',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-07-19',
                'telefono' => '72233445',
                'registro_universitario' => '219022334',
                'anio_egreso' => 2025,
                'ppa' => '94.00',
                'likert_patron' => [5, 5, 4, 5, 5],
            ],
            [
                'meta_fase' => 7,
                'correo' => 'fase7.entrevista@talento.test',
                'password' => 'password123',
                'nombres' => 'Cristian',
                'apellido_paterno' => 'Torrico',
                'apellido_materno' => 'Dorado',
                'ci_numero' => '8345673',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2001-11-25',
                'telefono' => '73344556',
                'registro_universitario' => '219033445',
                'anio_egreso' => 2025,
                'ppa' => '95.80',
                'likert_patron' => [5, 5, 5, 5, 4],
            ],
            [
                'meta_fase' => 8,
                'correo' => 'fase8.adjudicada@talento.test',
                'password' => 'password123',
                'nombres' => 'Daniela',
                'apellido_paterno' => 'Rios',
                'apellido_materno' => 'Aguilera',
                'ci_numero' => '8456784',
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '2002-04-12',
                'telefono' => '74455667',
                'registro_universitario' => '219044556',
                'anio_egreso' => 2025,
                'ppa' => '98.50',
                'likert_patron' => [5, 5, 5, 5, 5],
            ],
        ];
    }
}