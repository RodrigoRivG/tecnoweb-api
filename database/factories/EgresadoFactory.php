<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\Egresado;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egresado>
 */
class EgresadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'carrera_id' => Carrera::factory(),
            'registro_universitario' => (string) fake()->unique()->numberBetween(200000000, 299999999),
            'anio_egreso' => fake()->numberBetween(2015, (int) date('Y')),
            'ppa' => '70.00',
            'titulo_verificado' => false,
        ];
    }

    public function conPpa(string $ppa): static
    {
        return $this->state(fn (): array => ['ppa' => $ppa]);
    }
}
