<?php

namespace Tests\Feature\Admin;

use App\Enums\CodigoRol;
use App\Enums\FamiliaPrueba;
use App\Models\Prueba;
use App\Models\ProcesoPrueba;
use App\Models\Usuario;
use Database\Seeders\AlinearBancoEvaluacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BancoJerarquiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_tiene_cuatro_familias_y_el_panel_las_lista(): void
    {
        $this->assertSame(
            [],
            Prueba::query()->where('familia', 'INTELIGENCIA_EMOCIONAL')->pluck('codigo')->all(),
        );
        $this->assertNull(Prueba::query()->where('codigo', 'TEST_APTITUD_NUMERICA')->first());
        $this->assertNull(Prueba::query()->where('codigo', '16PF_FORMA_A')->value('limite_preguntas'));

        foreach (['16PF_FORMA_A', '16PF_FORMA_B', 'ES_USTED_MUY_HABIL', 'DOMINOS_D48', 'RAVEN', 'TMMS_24', 'TEST_HABILIDADES_BLANDAS', 'TEST_APTITUD_MECANICA', 'TEST_CODIFICACION', 'TEST_LABERINTOS_VISOESPACIAL'] as $codigo) {
            $this->assertNotNull(Prueba::query()->where('codigo', $codigo)->first(), $codigo);
        }

        $this->assertCount(4, FamiliaPrueba::cases());

        $this->getJson(route('admin.banco-preguntas.jerarquia'))->assertUnauthorized();
        $this->patchJson(route('admin.familias.tiempo', ['familia' => 'PERSONALIDAD']), [
            'tiempo_pregunta_seg' => 45,
        ])->assertUnauthorized();

        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $this->actingAs($admin, 'sanctum')
            ->getJson(route('admin.banco-preguntas.jerarquia'))
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.codigo', 'PERSONALIDAD')
            ->assertJsonPath('data.0.modo', 'ALTERNA')
            ->assertJsonPath('data.0.tiempo_pregunta_seg', 60)
            ->assertJsonPath('data.3.codigo', 'HABILIDAD_ESPECIFICA')
            ->assertJsonPath('data.3.modo', 'POR_CARRERA');

        $this->actingAs(Usuario::factory()->conRol(CodigoRol::Egresado)->create(), 'sanctum')
            ->patchJson(route('admin.familias.tiempo', ['familia' => 'PERSONALIDAD']), [
                'tiempo_pregunta_seg' => 45,
            ])
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->patchJson(route('admin.familias.tiempo', ['familia' => 'PERSONALIDAD']), [
                'tiempo_pregunta_seg' => 45,
            ])
            ->assertOk()
            ->assertJsonPath('data.tiempo_pregunta_seg', 45);

        $this->assertSame(45, \App\Models\ConfiguracionFamilia::query()->where('familia', 'PERSONALIDAD')->value('tiempo_pregunta_seg'));

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('admin.banco-preguntas.jerarquia'))
            ->assertJsonPath('data.0.tiempo_pregunta_seg', 45);
    }

    public function test_informatica_recibe_tres_libros_y_la_etapa_blanda_no_se_duplica(): void
    {
        $carrera = \App\Models\Carrera::factory()
            ->for(\App\Models\Facultad::factory()->state(['ppa_minimo' => '51.00']))
            ->create(['nombre' => 'Ingeniería Informática']);
        $proceso = $this->crearConvocatoria($carrera);

        $this->seed(AlinearBancoEvaluacionSeeder::class);

        $vinculos = $carrera->pruebas()->orderBy('codigo')->pluck('codigo')->all();
        $this->assertSame(
            ['TEST_LABERINTOS_VISOESPACIAL', 'TEST_LECTURA_COMPRENSIVA', 'TEST_PURDUE_NO_VERBAL'],
            $vinculos,
        );

        $this->assertFalse(
            $proceso->etapas()->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'HABILIDADES_BLANDAS'))->exists(),
        );

        $codigos = ProcesoPrueba::query()
            ->whereHas('procesoEtapa', fn ($consulta) => $consulta
                ->where('proceso_id', $proceso->id)
                ->whereHas('etapa', fn ($etapa) => $etapa->where('codigo', 'EVALUACION_PSICOLOGICA')))
            ->with('prueba')
            ->get()
            ->map(fn ($cupo) => $cupo->prueba->codigo)
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            ['16PF_FORMA_A', 'ES_USTED_MUY_HABIL', 'TEST_LABERINTOS_VISOESPACIAL', 'TEST_LECTURA_COMPRENSIVA', 'TEST_PURDUE_NO_VERBAL', 'TMMS_24'],
            $codigos,
        );
    }
}
