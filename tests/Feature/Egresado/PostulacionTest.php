<?php

namespace Tests\Feature\Egresado;

use App\Enums\CodigoRol;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoProceso;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\Egresado;
use App\Models\Facultad;
use App\Models\HistorialPostulacionEtapa;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\ProcesoPrueba;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostulacionTest extends TestCase
{
    use RefreshDatabase;

    private Carrera $carrera;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
    }

    public function test_ppa_igual_al_minimo_postula_y_crea_etapas_resultado_e_historial(): void
    {
        $usuario = $this->crearEgresado($this->carrera, '51.00');
        $proceso = $this->crearConvocatoria($this->carrera);

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertCreated()
            ->assertJsonPath('data.estado', 'EN_PROCESO')
            ->assertJsonPath('data.convocatoria.proceso_id', $proceso->id)
            ->assertJsonPath('data.etapas', [
                ['codigo' => 'INSCRIPCION', 'nombre' => 'Inscripción', 'orden' => 1, 'estado' => 'APROBADA'],
                ['codigo' => 'REQUISITOS', 'nombre' => 'Requisitos', 'orden' => 2, 'estado' => 'PENDIENTE'],
                ['codigo' => 'EVALUACION_TECNICA', 'nombre' => 'Evaluación técnica', 'orden' => 3, 'estado' => 'APROBADA'],
                ['codigo' => 'ANALISIS_CV', 'nombre' => 'Análisis de CV', 'orden' => 4, 'estado' => 'BLOQUEADA'],
                ['codigo' => 'ENTREVISTA', 'nombre' => 'Entrevista', 'orden' => 5, 'estado' => 'BLOQUEADA'],
                ['codigo' => 'SELECCION', 'nombre' => 'Selección', 'orden' => 6, 'estado' => 'BLOQUEADA'],
            ])
            ->assertJsonCount(4, 'data.requisitos')
            ->assertJsonPath('data.requisitos_completos', false);
        $this->assertMatchesRegularExpression('/^T\d{2}-[A-HJ-NP-Z2-9]{6}$/', $respuesta->json('data.codigo'));

        $postulacion = Postulacion::query()->sole();
        $this->assertSame(EstadoPostulacion::EnProceso, $postulacion->estado);
        $this->assertDatabaseHas('resultado_prueba', [
            'postulacion_id' => $postulacion->id,
            'puntaje_bruto' => '51.00',
            'puntaje_normalizado' => '51.00',
            'umbral_aplicado' => '51.00',
            'aprobada' => true,
        ]);
        $historial = HistorialPostulacionEtapa::query()->get();
        $this->assertCount(6, $historial);
        $this->assertSame([$usuario->id], $historial->pluck('usuario_id')->unique()->values()->all());
    }

    public function test_ppa_menor_al_minimo_rechaza_sin_guardar_nada(): void
    {
        $usuario = $this->crearEgresado($this->carrera, '50.99');
        $proceso = $this->crearConvocatoria($this->carrera);

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'proceso_id' => 'Tu PPA (50.99) no alcanza el mínimo de 51.00 exigido por la facultad.',
        ]);
        $this->assertDatabaseCount('postulacion', 0);
        $this->assertDatabaseCount('postulacion_etapa', 0);
        $this->assertDatabaseCount('resultado_prueba', 0);
    }

    public function test_el_cambio_del_ppa_minimo_de_la_facultad_rige_la_siguiente_postulacion(): void
    {
        $usuario = $this->crearEgresado($this->carrera, '55.00');
        $proceso = $this->crearConvocatoria($this->carrera);
        $this->carrera->facultad->update(['ppa_minimo' => '60.00']);

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'proceso_id' => 'Tu PPA (55.00) no alcanza el mínimo de 60.00 exigido por la facultad.',
        ]);
    }

    public function test_umbral_fijo_usa_el_puntaje_minimo_de_la_prueba_ppa(): void
    {
        $usuario = $this->crearEgresado($this->carrera, '55.00');
        $proceso = $this->crearConvocatoria($this->carrera);
        ProcesoPrueba::query()->whereIn('proceso_etapa_id', $proceso->etapas()->pluck('id'))
            ->where('umbral_origen', 'FACULTAD')
            ->update(['umbral_origen' => 'FIJO', 'puntaje_minimo' => '45.00']);

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertCreated();
        $this->assertDatabaseHas('resultado_prueba', ['umbral_aplicado' => '45.00']);
    }

    public function test_no_puede_postular_a_una_convocatoria_de_otra_carrera(): void
    {
        $usuario = $this->crearEgresado($this->carrera);
        $proceso = $this->crearConvocatoria(Carrera::factory()->create());

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['proceso_id' => 'La convocatoria no corresponde a tu carrera.']);
        $this->assertDatabaseCount('postulacion', 0);
    }

    public function test_no_puede_postular_a_una_convocatoria_cerrada_o_inexistente(): void
    {
        $usuario = $this->crearEgresado($this->carrera);
        $cerrada = ConvocatoriaTalento::factory()->for($this->carrera)->cerrada()->create();
        $enBorrador = ConvocatoriaTalento::factory()->for($this->carrera)->for(Proceso::factory()->conEstado(EstadoProceso::Borrador))->create();

        foreach ([$cerrada->proceso_id, $enBorrador->proceso_id, 999999] as $procesoId) {
            $this->actingAs($usuario, 'sanctum')
                ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $procesoId])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['proceso_id' => 'La convocatoria no está abierta.']);
        }
        $this->assertDatabaseCount('postulacion', 0);
    }

    public function test_no_puede_postular_dos_veces_a_la_misma_convocatoria(): void
    {
        $usuario = $this->crearEgresado($this->carrera);
        $proceso = $this->crearConvocatoria($this->carrera);
        $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();

        $respuesta = $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id]);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['proceso_id' => 'Ya postulaste a esta convocatoria.']);
        $this->assertDatabaseCount('postulacion', 1);
    }

    public function test_convocatorias_del_egresado_indica_si_cumple_el_ppa_y_si_ya_postulo(): void
    {
        $usuario = $this->crearEgresado($this->carrera, '50.00');
        $otraCarrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '45.00']))->create();
        Egresado::factory()->for($usuario->persona)->for($otraCarrera)->conPpa('80.00')->create();
        $noCumple = $this->crearConvocatoria($this->carrera);
        $cumple = $this->crearConvocatoria($otraCarrera);
        $this->crearConvocatoria(Carrera::factory()->create());
        $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $cumple->id])->assertCreated();

        $respuesta = $this->actingAs($usuario, 'sanctum')->getJson(route('egresado.convocatorias'));

        $respuesta->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.proceso_id', $noCumple->id)
            ->assertJsonPath('data.0.cumple_ppa', false)
            ->assertJsonPath('data.0.ya_postulado', false)
            ->assertJsonPath('data.1.proceso_id', $cumple->id)
            ->assertJsonPath('data.1.cumple_ppa', true)
            ->assertJsonPath('data.1.ya_postulado', true)
            ->assertJsonPath('data.1.postulacion_id', Postulacion::query()->value('id'));
    }

    public function test_lista_y_detalle_muestran_solo_las_postulaciones_propias(): void
    {
        $usuario = $this->crearEgresado($this->carrera);
        $otro = $this->crearEgresado($this->carrera);
        $proceso = $this->crearConvocatoria($this->carrera);
        $propia = $this->postular($usuario, $proceso);
        $ajena = $this->postular($otro, $proceso);

        $lista = $this->actingAs($usuario, 'sanctum')->getJson(route('egresado.postulaciones.index'));
        $detalle = $this->actingAs($usuario, 'sanctum')->getJson(route('egresado.postulaciones.show', $propia));
        $detalleAjeno = $this->actingAs($usuario, 'sanctum')->getJson(route('egresado.postulaciones.show', $ajena));

        $lista->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $propia->id)->assertJsonMissingPath('data.0.etapas');
        $detalle->assertOk()->assertJsonPath('data.id', $propia->id)->assertJsonCount(6, 'data.etapas');
        $detalleAjeno->assertNotFound();
    }

    public function test_otro_rol_no_puede_usar_las_rutas_del_egresado(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();

        $respuesta = $this->actingAs($admin, 'sanctum')->getJson(route('egresado.postulaciones.index'));

        $respuesta->assertForbidden()->assertJsonPath('message', 'Tu rol no tiene acceso a esta sección.');
    }

    public function test_sin_token_responde_401(): void
    {
        $respuesta = $this->postJson(route('egresado.postulaciones.store'), ['proceso_id' => 1]);

        $respuesta->assertUnauthorized();
    }

    private function postular(Usuario $usuario, Proceso $proceso): Postulacion
    {
        $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();

        return $usuario->persona->postulaciones()->where('proceso_id', $proceso->id)->sole();
    }
}
