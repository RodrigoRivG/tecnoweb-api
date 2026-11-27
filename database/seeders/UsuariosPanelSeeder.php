<?php

namespace Database\Seeders;

use App\Enums\CodigoRol;
use App\Enums\DepartamentoCi;
use App\Enums\EstadoUsuario;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Cuentas del panel: ayudantes (alcance global del sistema, sin carrera).
 */
class UsuariosPanelSeeder extends Seeder
{
    public function run(): void
    {
        if (App::isProduction()) {
            throw new RuntimeException('UsuariosPanelSeeder no se ejecuta en producción.');
        }

        $rolAyudante = Rol::query()->where('codigo', CodigoRol::Ayudante->value)->firstOrFail();

        foreach (self::definiciones() as $def) {
            $this->sembrarAyudante($rolAyudante->id, $def);
        }

        $this->command?->info('Ayudantes de panel listos (correo @talento.test · contraseña ayudante12345).');
    }

    /**
     * @return list<array{
     *     correo: string,
     *     nombres: string,
     *     apellido_paterno: string,
     *     apellido_materno: string,
     *     ci_numero: string
     * }>
     */
    private static function definiciones(): array
    {
        return [
            [
                'correo' => 'ayudante@talento.test',
                'nombres' => 'Laura',
                'apellido_paterno' => 'Mendoza',
                'apellido_materno' => 'Vega',
                'ci_numero' => '7790123',
            ],
            [
                'correo' => 'ayudante2@talento.test',
                'nombres' => 'Marco',
                'apellido_paterno' => 'Quispe',
                'apellido_materno' => 'Rojas',
                'ci_numero' => '7791234',
            ],
        ];
    }

    /**
     * @param  array{
     *     correo: string,
     *     nombres: string,
     *     apellido_paterno: string,
     *     apellido_materno: string,
     *     ci_numero: string
     * }  $def
     */
    private function sembrarAyudante(int $rolId, array $def): void
    {
        $usuario = Usuario::query()->updateOrCreate(
            ['correo' => $def['correo']],
            [
                'rol_id' => $rolId,
                'password_hash' => Hash::make('ayudante12345'),
                'estado' => EstadoUsuario::Activo,
            ],
        );

        Persona::query()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'nombres' => $def['nombres'],
                'apellido_paterno' => $def['apellido_paterno'],
                'apellido_materno' => $def['apellido_materno'],
                'ci_numero' => $def['ci_numero'],
                'ci_complemento' => '',
                'ci_expedido' => DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => '1995-05-15',
                'telefono' => '71122334',
                'correo_contacto' => $def['correo'],
            ],
        );
    }
}