<?php

namespace Database\Factories;

use App\Enums\UmbralOrigen;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Etapa;
use App\Models\Proceso;
use App\Models\Prueba;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Crea la convocatoria con las etapas y requisitos típicos del flujo A.
 * Requiere los catálogos (CatalogoSeeder).
 *
 * @extends Factory<ConvocatoriaTalento>
 */
class ConvocatoriaTalentoFactory extends Factory
{
    /**
     * Etapas del flujo A en orden, con la prueba que contiene cada una.
     *
     * @var array<string, array{prueba: ?string, peso: string}>
     */
    public const ETAPAS_FLUJO_A = [
        'INSCRIPCION' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_TECNICA' => ['prueba' => Prueba::PPA, 'peso' => '20'],
        'REQUISITOS' => ['prueba' => null, 'peso' => '0'],
        'EVALUACION_PSICOLOGICA' => ['prueba' => 'TEST_PERSONALIDAD', 'peso' => '25'],
        'HABILIDADES_BLANDAS' => ['prueba' => 'TEST_HABILIDADES_BLANDAS', 'peso' => '20'],
        'ANALISIS_CV' => ['prueba' => 'CV_ANALISIS', 'peso' => '15'],
        'ENTREVISTA' => ['prueba' => 'ENTREVISTA', 'peso' => '20'],
        'SELECCION' => ['prueba' => null, 'peso' => '0'],
    ];



    /**
     * Documentos que exige la convocatoria (todos obligatorios).
     *
     * @var list<string>
     */
    public const REQUISITOS_FLUJO_A = ['HISTORICO_ACADEMICO', 'CEDULA_IDENTIDAD', 'FOTOGRAFIA', 'CURRICULUM_VITAE'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proceso_id' => Proceso::factory(),
            'carrera_id' => Carrera::factory(),
            'gestion' => (int) date('Y'),
            'numero' => fake()->unique()->numberBetween(1, 30000),
            'fecha_apertura' => self::hoy()->subWeek()->toDateString(),
            'fecha_cierre' => self::hoy()->addMonth()->toDateString(),
            'vigencia_perfil_meses' => 12,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ConvocatoriaTalento $convocatoria): void {
            $proceso = $convocatoria->proceso;
            $etapas = Etapa::query()->whereIn('codigo', array_keys(self::ETAPAS_FLUJO_A))->pluck('id', 'codigo');
            $pruebas = Prueba::query()->pluck('id', 'codigo');
            $orden = 1;

            foreach (self::ETAPAS_FLUJO_A as $codigoEtapa => $configuracion) {
                $procesoEtapa = $proceso->etapas()->create([
                    'etapa_id' => $etapas[$codigoEtapa],
                    'orden' => $orden++,
                ]);

                if ($configuracion['prueba'] !== null) {
                    $procesoEtapa->pruebas()->create([
                        'prueba_id' => $pruebas[$configuracion['prueba']],
                        'peso' => $configuracion['peso'],
                        'umbral_origen' => $configuracion['prueba'] === Prueba::PPA ? UmbralOrigen::Facultad : UmbralOrigen::Fijo,
                    ]);
                }
            }

            $requisitos = TipoDocumento::query()->whereIn('codigo', self::REQUISITOS_FLUJO_A)->pluck('id');
            $proceso->tiposDocumento()->attach($requisitos, ['obligatorio' => true]);
        });
    }

    public function cerrada(): static
    {
        return $this->state(fn (): array => [
            'fecha_apertura' => self::hoy()->subMonths(2)->toDateString(),
            'fecha_cierre' => self::hoy()->subDay()->toDateString(),
        ]);
    }

    public function futura(): static
    {
        return $this->state(fn (): array => [
            'fecha_apertura' => self::hoy()->addWeek()->toDateString(),
            'fecha_cierre' => self::hoy()->addMonth()->toDateString(),
        ]);
    }

    /**
     * Fecha de hoy en la zona horaria del negocio (la que usa la regla R5).
     */
    private static function hoy(): Carbon
    {
        return today(config('app.zona_horaria_negocio'));
    }
}
