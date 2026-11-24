<?php

namespace Tests\Feature\Egresado;

use App\Enums\EstadoEtapa;
use App\Enums\TipoPrueba;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\IntentoPrueba;
use App\Models\Postulacion;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TiempoPreguntaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pregunta_vencida_queda_en_cero_y_abre_la_siguiente(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $proceso = $this->crearConvocatoria($carrera);
        $libro = Prueba::query()->where('codigo', '16PF_FORMA_A')->firstOrFail();

        $preguntas = collect([1, 2])->map(function (int $orden) use ($libro) {
            $pregunta = $libro->preguntas()->create([
                'tipo_prueba' => TipoPrueba::Test->value,
                'orden' => $orden,
                'enunciado' => 'Ítem '.$orden,
            ]);
            $pregunta->opciones()->create(['etiqueta' => 'A', 'texto' => 'Opción', 'puntaje' => 1]);

            return $pregunta;
        });

        $egresado = $this->crearEgresado($carrera, '70.00');
        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();

        $postulacion = Postulacion::query()->where('proceso_id', $proceso->id)->firstOrFail();
        $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->update(['estado' => EstadoEtapa::Pendiente->value]);

        $cupo = ProcesoPrueba::query()
            ->where('prueba_id', $libro->id)
            ->whereHas('procesoEtapa', fn ($consulta) => $consulta->where('proceso_id', $proceso->id))
            ->firstOrFail();

        $inicio = $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.evaluacion.iniciar', ['postulacion' => $postulacion->id]), [
                'proceso_prueba_id' => $cupo->id,
            ]);

        $inicio->assertCreated();
        $intentoId = $inicio->json('data.intento_id');

        $abierta = $this->actingAs($egresado, 'sanctum')
            ->getJson(route('egresado.intentos.preguntas', ['intento' => $intentoId]));

        $abierta->assertOk()
            ->assertJsonPath('data.preguntas.0.id', $preguntas[0]->id)
            ->assertJsonPath('data.cerrado_por_tiempo', false);
        $this->assertGreaterThan(50, $abierta->json('data.intento.segundos_restantes'));

        IntentoPrueba::query()->whereKey($intentoId)->update([
            'pregunta_actual_iniciada_en' => now()->subSeconds(120),
        ]);

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.intentos.responder', ['intento' => $intentoId]), [
                'pregunta_id' => $preguntas[0]->id,
                'opcion_id' => $preguntas[0]->opciones()->value('id'),
            ])
            ->assertOk()
            ->assertJsonPath('data.expirada', true)
            ->assertJsonPath('data.opcion_id', null);

        $siguiente = $this->actingAs($egresado, 'sanctum')
            ->getJson(route('egresado.intentos.preguntas', ['intento' => $intentoId]));

        $siguiente->assertOk()
            ->assertJsonPath('data.preguntas.0.id', $preguntas[1]->id)
            ->assertJsonPath('data.cerrado_por_tiempo', false);

        $this->assertTrue((bool) DB::table('talento.respuesta_intento')
            ->where('intento_id', $intentoId)
            ->where('pregunta_id', $preguntas[0]->id)
            ->value('expirada'));
    }
}
