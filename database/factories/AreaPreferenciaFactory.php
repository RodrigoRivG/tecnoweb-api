<?php

namespace Database\Factories;

use App\Models\AreaPreferencia;
use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AreaPreferencia>
 */
class AreaPreferenciaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carrera_id' => Carrera::factory(),
            'nombre' => fake()->unique()->word(),
        ];
    }
}
