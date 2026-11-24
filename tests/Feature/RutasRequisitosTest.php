<?php

namespace Tests\Feature;

use App\Enums\CodigoRol;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Postulacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RutasRequisitosTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_rutas_de_los_requisitos_estan_registradas(): void
    {
        $nombres = [
            'egresado.pruebas.modulos',
            'egresado.evaluacion.activa',
            'egresado.evaluacion.iniciar',
            'egresado.intentos.preguntas',
            'egresado.intentos.responder',
            'egresado.intentos.finalizar',
            'evaluador.postulaciones.entrevista',
            'evaluador.postulaciones.calificar-entrevista',
            'evaluador.postulaciones.informe',
            'admin.procesos.perfil-ideal.show',
            'admin.procesos.perfil-ideal.update',
        ];

        foreach ($nombres as $nombre) {
            $this->assertTrue(Route::has($nombre), "Falta la ruta {$nombre}");
        }
    }

    public function test_sin_sesion_responden_401_y_no_404(): void
    {
        $this->getJson(route('egresado.pruebas.modulos', 1))->assertUnauthorized();
        $this->getJson(route('egresado.intentos.preguntas', 1))->assertUnauthorized();
        $this->postJson(route('egresado.intentos.responder', 1))->assertUnauthorized();
        $this->postJson(route('egresado.intentos.finalizar', 1))->assertUnauthorized();
        $this->getJson(route('evaluador.postulaciones.entrevista', 1))->assertUnauthorized();
        $this->postJson(route('evaluador.postulaciones.calificar-entrevista', 1))->assertUnauthorized();
        $this->getJson(route('evaluador.postulaciones.informe', 1))->assertUnauthorized();
        $this->getJson(route('admin.procesos.perfil-ideal.show', 1))->assertUnauthorized();
        $this->putJson(route('admin.procesos.perfil-ideal.update', 1))->assertUnauthorized();
    }

    public function test_el_egresado_recibe_403_en_entrevista_informe_y_perfil_ideal(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $egresado = $this->crearEgresado($carrera, '70.00');
        $proceso = $this->crearConvocatoria($carrera);

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();

        $postulacion = Postulacion::query()->where('proceso_id', $proceso->id)->firstOrFail();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('evaluador.postulaciones.entrevista', $postulacion))
            ->assertForbidden();

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('evaluador.postulaciones.calificar-entrevista', $postulacion), [])
            ->assertForbidden();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('evaluador.postulaciones.informe', $postulacion))
            ->assertForbidden();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('admin.procesos.perfil-ideal.show', $proceso))
            ->assertForbidden();

        $this->actingAs(Usuario::factory()->conRol(CodigoRol::Admin)->create(), 'sanctum')
            ->getJson(route('admin.procesos.perfil-ideal.show', $proceso))
            ->assertOk()
            ->assertJsonStructure(['data' => ['perfil_personalidad', 'perfil_habilidades_blandas']]);
    }
}
