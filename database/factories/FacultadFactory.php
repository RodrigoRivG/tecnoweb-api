<?php

namespace Database\Factories;

use App\Models\Facultad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facultad>
 */
class FacultadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Facultad de '.fake()->unique()->words(3, true),
            'sigla' => fake()->unique()->lexify('F???'),
            'ppa_minimo' => '51.00',
            'activa' => true,
        ];
    }
}
