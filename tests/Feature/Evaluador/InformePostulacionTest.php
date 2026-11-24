<?php

namespace Tests\Feature\Evaluador;

use App\Enums\CodigoRol;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Models\Facultad;
use App\Models\Postulacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformePostulacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_perfil_ideal_y_el_informe_comparten_los_dos_cuadros(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $egresado = $this->crearEgresado($carrera, '70.00');
        $proceso = $this->crearConvocatoria($carrera);

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();

        $factorA = DimensionEvaluada::query()->where('factor', 'A')->firstOrFail();
        $atencion = DimensionEvaluada::query()->where('codigo', 'ATENCION_EMOCIONAL')->firstOrFail();

        $guardado = $this->actingAs($admin, 'sanctum')->putJson(route('admin.procesos.perfil-ideal.update', $proceso), [
            'niveles' => [
                ['dimension_id' => $factorA->id, 'valor_ideal' => 8],
                ['dimension_id' => $atencion->id, 'valor_ideal' => 6],
            ],
        ]);

        $guardado->assertOk()
            ->assertJsonPath('data.perfil_personalidad.0.codigo', 'A')
            ->assertJsonPath('data.perfil_personalidad.0.esten_ideal', 8)
            ->assertJsonPath('data.perfil_habilidades_blandas.0.codigo', 'ATENCION_EMOCIONAL')
            ->assertJsonPath('data.perfil_habilidades_blandas.0.valor_ideal', 6);

        $this->assertCount(16, $guardado->json('data.perfil_personalidad'));

        $postulacion = Postulacion::query()->where('proceso_id', $proceso->id)->firstOrFail();

        $informe = $this->actingAs($admin, 'sanctum')
            ->getJson(route('evaluador.postulaciones.informe', $postulacion));

        $informe->assertOk()
            ->assertJsonPath('data.perfil_personalidad.0.codigo', 'A')
            ->assertJsonPath('data.perfil_personalidad.0.esten_ideal', 8)
            ->assertJsonPath('data.perfil_personalidad.0.esten_real', null)
            ->assertJsonPath('data.perfil_habilidades_blandas.0.valor_ideal', 6)
            ->assertJsonPath('data.perfil_habilidades_blandas.0.valor_real', null)
            ->assertJsonPath('data.resumen_puntajes.entrevista.criterios.0.nombre', 'Presencia')
            ->assertJsonPath('data.resumen_puntajes.entrevista.criterios.3.nombre', 'Conocimientos técnicos')
            ->assertJsonPath('data.resumen_puntajes.total_general.maximo', 100)
            ->assertJsonStructure([
                'data' => [
                    'porcentaje_compatibilidad_global',
                    'perfil_personalidad',
                    'perfil_habilidades_blandas',
                    'resumen_puntajes' => [
                        'inteligencia',
                        'habilidades_especificas',
                        'entrevista' => ['total', 'criterios'],
                        'total_general',
                    ],
                ],
            ]);
    }

    public function test_un_egresado_no_puede_leer_el_informe(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $egresado = $this->crearEgresado($carrera, '70.00');
        $proceso = $this->crearConvocatoria($carrera);

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();

        $postulacion = Postulacion::query()->where('proceso_id', $proceso->id)->firstOrFail();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('evaluador.postulaciones.informe', $postulacion))
            ->assertForbidden();
    }
}
