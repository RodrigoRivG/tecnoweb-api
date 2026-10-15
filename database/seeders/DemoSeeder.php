<?php

namespace Database\Seeders;

use App\Enums\CodigoRol;
use App\Enums\EstadoUsuario;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Facultad;
use App\Models\Proceso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Datos de prueba para local y testing. El catálogo académico real lo carga el administrador desde el panel.
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('DemoSeeder no se ejecuta en producción.');
        }

        $admin = Usuario::query()->firstOrCreate(
            ['correo' => 'admin@talento.test'],
            ['rol_id' => Rol::idDe(CodigoRol::Admin), 'password_hash' => Hash::make('admin12345'), 'estado' => EstadoUsuario::Activo],
        );

        $catalogo = [
            ['nombre' => 'Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones', 'sigla' => 'FICCT', 'ppa_minimo' => '51.00',
                'carreras' => ['Ingeniería Informática' => [], 'Ingeniería de Sistemas' => [], 'Ingeniería en Redes y Telecomunicaciones' => []]],
            ['nombre' => 'Facultad de Ciencias Jurídicas, Políticas, Sociales y Relaciones Internacionales', 'sigla' => 'FCJPS', 'ppa_minimo' => '60.00',
                'carreras' => ['Derecho' => ['Penal', 'Civil', 'Laboral']]],
            ['nombre' => 'Facultad de Ciencias Económicas, Administrativas y Financieras', 'sigla' => 'FCEAF', 'ppa_minimo' => '51.00',
                'carreras' => ['Contaduría Pública' => [], 'Administración de Empresas' => []]],
        ];

        foreach ($catalogo as $datosFacultad) {
            $facultad = Facultad::query()->updateOrCreate(
                ['sigla' => $datosFacultad['sigla']],
                ['nombre' => $datosFacultad['nombre'], 'ppa_minimo' => $datosFacultad['ppa_minimo']],
            );

            foreach ($datosFacultad['carreras'] as $nombreCarrera => $areas) {
                $carrera = Carrera::query()->firstOrCreate(['facultad_id' => $facultad->id, 'nombre' => $nombreCarrera]);

                foreach ($areas as $area) {
                    $carrera->areas()->firstOrCreate(['nombre' => $area]);
                }
            }
        }

        $gestion = (int) today()->format('Y');

        Carrera::query()
            ->whereHas('facultad', fn ($facultad) => $facultad->where('sigla', 'FICCT'))
            ->whereDoesntHave('convocatorias')
            ->get()
            ->each(function (Carrera $carrera) use ($admin, $gestion): void {
                $proceso = Proceso::factory()->create([
                    'codigo' => sprintf('TAL-%d-%03d', $gestion, $carrera->id),
                    'nombre' => "Talento {$carrera->nombre} {$gestion}",
                    'creado_por' => $admin->id,
                ]);

                ConvocatoriaTalento::factory()->create([
                    'proceso_id' => $proceso->id,
                    'carrera_id' => $carrera->id,
                    'gestion' => $gestion,
                    'numero' => 1,
                ]);
            });
    }
}
