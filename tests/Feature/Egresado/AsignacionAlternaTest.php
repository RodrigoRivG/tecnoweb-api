<?php

namespace Tests\Feature\Egresado;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
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
use App\Models\Prueba;
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
