<?php

namespace Database\Factories;

use App\Enums\DepartamentoCi;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'nombres' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'ci_numero' => (string) fake()->unique()->numberBetween(1000000, 99999999),
            'ci_complemento' => '',
            'ci_expedido' => fake()->randomElement(DepartamentoCi::cases()),
            'fecha_nacimiento' => fake()->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
            'telefono' => fake()->numerify('7#######'),
            'correo_contacto' => fake()->safeEmail(),
        ];
    }

    /**
     * Persona registrada por terceros, sin cuenta de acceso.
     */
    public function sinUsuario(): static
    {
        return $this->state(fn (): array => ['usuario_id' => null]);
    }
}
