<?php

namespace Database\Factories;

use App\Enums\CodigoRol;
use App\Enums\EstadoUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Requiere el catálogo de roles (CatalogoSeeder).
 *
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rol_id' => fn (): int => Rol::idDe(CodigoRol::Egresado),
            'correo' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password1'),
            'estado' => EstadoUsuario::Activo,
            'intentos_fallidos' => 0,
        ];
    }

    public function conRol(CodigoRol $rol): static
    {
        return $this->state(fn (): array => ['rol_id' => Rol::idDe($rol)]);
    }

    public function conEstado(EstadoUsuario $estado): static
    {
        return $this->state(fn (): array => ['estado' => $estado]);
    }
}
