<?php

namespace Database\Seeders\Concerns;

use App\Enums\EstadoUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Usuario técnico para FK de archivos al sembrar bancos (producción sin DemoSeeder).
 */
final class UsuarioSemillaSistema
{
    private static ?int $id = null;

    public static function id(): int
    {
        if (self::$id !== null) {
            return self::$id;
        }

        $rolId = Rol::query()->where('codigo', 'ADMIN')->value('id');
        if ($rolId === null) {
            throw new RuntimeException('Falta el rol ADMIN: ejecute CatalogoSeeder antes del banco de preguntas.');
        }

        $usuario = Usuario::query()->firstOrCreate(
            ['correo' => 'sistema.seeder@talento.local'],
            [
                'rol_id' => $rolId,
                'password_hash' => Hash::make(Str::password(24)),
                'estado' => EstadoUsuario::Activo,
            ],
        );

        self::$id = (int) $usuario->id;

        return self::$id;
    }
}