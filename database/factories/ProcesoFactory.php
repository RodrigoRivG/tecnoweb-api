<?php

namespace Database\Factories;

use App\Enums\CodigoRol;
use App\Enums\EstadoProceso;
use App\Enums\TipoProceso;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proceso>
 */
class ProcesoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('PRC-####-????'),
            'tipo' => TipoProceso::Talento,
            'nombre' => 'Convocatoria '.fake()->words(3, true),
            'estado' => EstadoProceso::Abierto,
            'top_n' => 5,
            'creado_por' => Usuario::factory()->conRol(CodigoRol::Admin),
        ];
    }

    public function conEstado(EstadoProceso $estado): static
    {
        return $this->state(fn (): array => ['estado' => $estado]);
    }
}
