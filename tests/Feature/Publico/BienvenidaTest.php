<?php

namespace Tests\Feature\Publico;

use App\Enums\EstadoProceso;
use App\Enums\TipoConsentimiento;
use App\Models\AreaPreferencia;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Facultad;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\ProcesoPrueba;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BienvenidaTest extends TestCase
{
    use RefreshDatabase;

    public function test_facultades_lista_solo_las_activas_con_sus_carreras_y_areas_sin_ppa_minimo(): void
    {
        $facultad = Facultad::factory()->create(['nombre' => 'Facultad A', 'sigla' => 'FA']);
        $carrera = Carrera::factory()->for($facultad)->create(['nombre' => 'Derecho']);
        AreaPreferencia::factory()->for($carrera)->create(['nombre' => 'Penal']);
        Carrera::factory()->for($facultad)->create(['nombre' => 'Cerrada', 'activa' => false]);
        Facultad::factory()->create(['nombre' => 'Facultad inactiva', 'activa' => false]);

        $respuesta = $this->getJson(route('publico.facultades'));

        $respuesta->assertOk()
            ->assertExactJson(['data' => [[
                'id' => $facultad->id,
                'nombre' => 'Facultad A',
                'sigla' => 'FA',
                'carreras' => [[
                    'id' => $carrera->id,
                    'nombre' => 'Derecho',
                    'areas' => [['id' => $carrera->areas->first()->id, 'nombre' => 'Penal']],
                ]],
            ]]]);
    }

    public function test_convocatorias_lista_solo_las_abiertas_y_en_fecha_con_el_ppa_minimo_de_la_facultad(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '60.00']))->create();
        $abierta = $this->crearConvocatoria($carrera);
        ConvocatoriaTalento::factory()->for($carrera)->cerrada()->create();
        ConvocatoriaTalento::factory()->for($carrera)->futura()->create();
        ConvocatoriaTalento::factory()->for($carrera)
            ->for(Proceso::factory()->conEstado(EstadoProceso::Borrador))
            ->create();

        $respuesta = $this->getJson(route('publico.convocatorias'));

        $respuesta->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.proceso_id', $abierta->id)
            ->assertJsonPath('data.0.carrera.id', $carrera->id)
            ->assertJsonPath('data.0.ppa_minimo', '60.00')
            ->assertJsonMissingPath('data.0.cumple_ppa');
    }

    public function test_convocatorias_usa_el_puntaje_minimo_si_el_umbral_es_fijo(): void
    {
        $proceso = $this->crearConvocatoria(Carrera::factory()->create());
        ProcesoPrueba::query()->whereIn('proceso_etapa_id', $proceso->etapas()->pluck('id'))
            ->where('umbral_origen', 'FACULTAD')
            ->update(['umbral_origen' => 'FIJO', 'puntaje_minimo' => '75.50']);

        $respuesta = $this->getJson(route('publico.convocatorias'));

        $respuesta->assertOk()->assertJsonPath('data.0.ppa_minimo', '75.50');
    }

    public function test_convocatorias_filtra_por_carrera_y_por_facultad(): void
    {
        $carrera = Carrera::factory()->create();
        $otraCarrera = Carrera::factory()->create();
        $buscada = $this->crearConvocatoria($carrera);
        $this->crearConvocatoria($otraCarrera);

        $porCarrera = $this->getJson(route('publico.convocatorias', ['carrera_id' => $carrera->id]));
        $porFacultad = $this->getJson(route('publico.convocatorias', ['facultad_id' => $carrera->facultad_id]));

        $porCarrera->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.proceso_id', $buscada->id);
        $porFacultad->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.proceso_id', $buscada->id);
    }

    public function test_perfiles_muestra_solo_rankings_publicados_con_consentimiento_y_sin_datos_personales(): void
    {
        $carrera = Carrera::factory()->create();
        $proceso = $this->crearConvocatoria($carrera);
        $conConsentimiento = $this->publicarEnRanking($proceso, $this->crearEgresado($carrera), posicion: 1, consiente: true);
        $this->publicarEnRanking($proceso, $this->crearEgresado($carrera), posicion: 2, consiente: false);

        $respuesta = $this->getJson(route('publico.perfiles'));

        $respuesta->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo_candidato', $conConsentimiento->codigo)
            ->assertJsonPath('data.0.posicion', 1)
            ->assertJsonPath('data.0.puntaje_total', '80.00');
        $this->assertSame(
            ['codigo_candidato', 'facultad', 'carrera', 'gestion', 'anio_egreso', 'posicion', 'puntaje_total', 'es_top', 'publicado_en'],
            array_keys($respuesta->json('data.0')),
        );
    }

    public function test_perfiles_vacio_cuando_no_hay_ranking_publicado(): void
    {
        $respuesta = $this->getJson(route('publico.perfiles'));

        $respuesta->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
    }

    public function test_los_filtros_publicos_rechazan_valores_no_numericos(): void
    {
        $respuesta = $this->getJson(route('publico.convocatorias', ['carrera_id' => 'abc']));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['carrera_id' => 'número entero']);
    }

    /**
     * El ranking lo genera otro spec; aquí se inserta directo para alimentar la vista.
     */
    private function publicarEnRanking(Proceso $proceso, Usuario $usuario, int $posicion, bool $consiente): Postulacion
    {
        $persona = $usuario->persona;
        $postulacion = $persona->postulaciones()->create([
            'codigo' => Postulacion::generarCodigo('T', 2026),
            'proceso_id' => $proceso->id,
        ]);
        $persona->consentimientos()->create([
            'tipo' => TipoConsentimiento::PublicacionPerfil,
            'version_politica' => '1.0',
            'otorgado' => $consiente,
        ]);
        DB::table('ranking_resultado')->insert([
            'proceso_id' => $proceso->id,
            'postulacion_id' => $postulacion->id,
            'posicion' => $posicion,
            'puntaje_total' => '80.00',
            'es_top' => true,
            'visible' => true,
            'publicado_en' => now(),
        ]);

        return $postulacion;
    }
}
