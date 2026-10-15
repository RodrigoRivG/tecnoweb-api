<?php

namespace Tests;

use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Egresado;
use App\Models\Proceso;
use App\Models\Usuario;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los tests con RefreshDatabase parten de los catálogos fijos.
     */
    protected bool $seed = true;

    /**
     * @var class-string
     */
    protected string $seeder = CatalogoSeeder::class;

    /**
     * Usuario egresado (con persona y perfil) de la carrera indicada.
     */
    protected function crearEgresado(Carrera $carrera, string $ppa = '70.00'): Usuario
    {
        return Egresado::factory()->for($carrera)->conPpa($ppa)->create()->persona->usuario;
    }

    /**
     * Convocatoria abierta del flujo A (etapas y requisitos estándar) para la carrera indicada.
     */
    protected function crearConvocatoria(Carrera $carrera): Proceso
    {
        return ConvocatoriaTalento::factory()->for($carrera)->create()->proceso;
    }
}
