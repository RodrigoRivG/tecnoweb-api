<?php

namespace Tests\Feature\Egresado;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\EstadoEtapa;
use App\Enums\FamiliaPrueba;
use App\Enums\ModoAsignacion;
use App\Enums\TipoPrueba;
use App\Enums\UmbralOrigen;
use App\Models\AsignacionPrueba;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Postulacion;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\RespuestaIntento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionAlternaTest extends TestCase
{
    use RefreshDatabase;

    public function test_habilidades_blandas_rotan_entre_postulantes(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $proceso = $this->crearConvocatoria($carrera);

        $this->conPregunta(Prueba::query()->where('codigo', 'TMMS_24')->firstOrFail());
        $this->conPregunta(Prueba::query()->where('codigo', 'TEST_HABILIDADES_BLANDAS')->firstOrFail());

        $etapa = ProcesoEtapa::query()
            ->where('proceso_id', $proceso->id)
            ->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->firstOrFail();

        $etapa->pruebas()->create([
            'prueba_id' => Prueba::query()->where('codigo', 'TMMS_24')->value('id'),
            'modo_asignacion' => ModoAsignacion::Alterna,
            'peso' => 12,
            'umbral_origen' => UmbralOrigen::Fijo,
            'obligatoria' => true,
        ]);

        $primero = $this->crearEgresado($carrera, '70.00');
        $segundo = $this->crearEgresado($carrera, '71.00');

        $this->actingAs($primero, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();
        $this->actingAs($segundo, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();

        $postulaciones = Postulacion::query()->where('proceso_id', $proceso->id)->orderBy('id')->get();
        foreach ($postulaciones as $postulacion) {
            $postulacion->etapas()
                ->whereHas('procesoEtapa.etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
                ->update(['estado' => EstadoEtapa::Pendiente->value]);

            app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());
        }

        $blandas = AsignacionPrueba::query()
            ->whereIn('postulacion_id', $postulaciones->pluck('id'))
            ->whereHas('procesoPrueba', fn ($consulta) => $consulta->where('modo_asignacion', ModoAsignacion::Alterna))
            ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', FamiliaPrueba::HabilidadBlanda))
            ->orderBy('postulacion_id')
            ->pluck('prueba_id');

        $this->assertCount(2, $blandas);
        $this->assertNotSame($blandas[0], $blandas[1]);
    }

    public function test_habilidad_especifica_asigna_un_libro_y_rota(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create([
            'nombre' => 'Ingeniería Informática',
        ]);
        $proceso = $this->crearConvocatoria($carrera);
        $codigos = ['TEST_CODIFICACION', 'TEST_LABERINTOS_VISOESPACIAL', 'TEST_LECTURA_COMPRENSIVA'];
        $libros = Prueba::query()->whereIn('codigo', $codigos)->get();
        $this->assertCount(3, $libros);
        foreach ($libros as $libro) {
            $this->conPregunta($libro);
        }
        $carrera->pruebas()->sync($libros->pluck('id'));

        $etapa = ProcesoEtapa::query()
            ->where('proceso_id', $proceso->id)
            ->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->firstOrFail();

        foreach ($libros->values() as $indice => $libro) {
            $etapa->pruebas()->create([
                'prueba_id' => $libro->id,
                'modo_asignacion' => ModoAsignacion::Fija,
                'orden' => 10 + $indice,
                'peso' => 5,
                'umbral_origen' => UmbralOrigen::Fijo,
                'obligatoria' => true,
            ]);
        }

        $primero = $this->crearEgresado($carrera, '70.00');
        $segundo = $this->crearEgresado($carrera, '71.00');
        $this->actingAs($primero, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();
        $this->actingAs($segundo, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();

        $postulaciones = Postulacion::query()->where('proceso_id', $proceso->id)->orderBy('id')->get();
        foreach ($postulaciones as $postulacion) {
            $postulacion->etapas()
                ->whereHas('procesoEtapa.etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
                ->update(['estado' => EstadoEtapa::Pendiente->value]);

            app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());
        }

        $asignadas = AsignacionPrueba::query()
            ->whereIn('postulacion_id', $postulaciones->pluck('id'))
            ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', FamiliaPrueba::HabilidadEspecifica))
            ->orderBy('postulacion_id')
            ->get();

        $this->assertCount(2, $asignadas);
        $this->assertNotSame($asignadas[0]->prueba_id, $asignadas[1]->prueba_id);
        $this->assertSame(1, ProcesoPrueba::query()
            ->where('proceso_etapa_id', $etapa->id)
            ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', FamiliaPrueba::HabilidadEspecifica))
            ->count());
    }

    public function test_se_puede_rendir_el_libro_rotado(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $proceso = $this->crearConvocatoria($carrera);
        $formaB = Prueba::query()->where('codigo', '16PF_FORMA_B')->firstOrFail();
        $this->conPregunta($formaB);

        $usuario = $this->crearEgresado($carrera, '70.00');
        $this->actingAs($usuario, 'sanctum')->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])->assertCreated();

        $postulacion = Postulacion::query()->where('proceso_id', $proceso->id)->firstOrFail();
        $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->update(['estado' => EstadoEtapa::Pendiente->value]);

        app(AsignarInstrumentosPostulacion::class)->handle($postulacion->refresh());

        $asignacion = AsignacionPrueba::query()->where('postulacion_id', $postulacion->id)->firstOrFail();
        $asignacion->update(['prueba_id' => $formaB->id]);

        $cupo = ProcesoPrueba::query()->findOrFail($asignacion->proceso_prueba_id);
        $intento = app(IniciarIntentoPrueba::class)->handle($usuario, $postulacion->refresh(), $cupo);

        $this->assertTrue(
            RespuestaIntento::query()->where('intento_id', $intento->id)->whereHas('pregunta', fn ($consulta) => $consulta->where('prueba_id', $formaB->id))->exists(),
        );
    }

    private function instrumento(string $codigo, string $nombre, FamiliaPrueba $familia): Prueba
    {
        return Prueba::query()->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo' => TipoPrueba::Test,
            'familia' => $familia,
            'es_plantilla' => true,
            'activa' => true,
        ]);
    }

    private function conPregunta(Prueba $prueba): void
    {
        $pregunta = $prueba->preguntas()->create([
            'tipo_prueba' => TipoPrueba::Test->value,
            'orden' => 1,
            'enunciado' => 'Ítem de '.$prueba->codigo,
        ]);

        $pregunta->opciones()->create([
            'etiqueta' => 'A',
            'texto' => 'Opción A',
            'puntaje' => 1,
        ]);
    }
}
