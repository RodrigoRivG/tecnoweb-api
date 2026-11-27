<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);
        $this->call(PermisoSeeder::class);
        $this->call(BancoPreguntasSeeder::class);
        $this->call(BancoPreguntasInteligenciaSeeder::class);
        $this->call(BancoPreguntasEmocionalSeeder::class);
        $this->call(BancoPreguntasLecturaSeeder::class);
        $this->call(BancoPreguntasLecturaTest1Seeder::class);
        $this->call(BancoPreguntasPurdueSeeder::class);
        $this->call(BancoPreguntasPurdueFormaBSeeder::class);
        $this->call(BancoPreguntasHabilidadesBlandasSeeder::class);
        $this->call(BancoPreguntas16PfFormaBSeeder::class);
        $this->call(BancoPreguntasDominosSeeder::class);
        $this->call(BancoPreguntasRavenSeeder::class);
        $this->call(BancoPreguntasCodificacionLetrasSimbolosSeeder::class);
        $this->call(BancoPreguntasEspecificasSeeder::class);
        $this->call(AlinearBancoEvaluacionSeeder::class);

        if (! App::isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
