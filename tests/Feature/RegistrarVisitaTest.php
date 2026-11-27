<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrarVisitaTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_visitas_se_cuentan_por_ruta_y_los_reintentos_no_duplican_el_total(): void
    {
        $inicio = ['ruta' => '/', 'clave_visita' => (string) Str::uuid()];

        $this->postJson(route('publico.visitas.store'), $inicio)
            ->assertOk()->assertJsonPath('data.total', 1);

        $this->postJson(route('publico.visitas.store'), $inicio)
            ->assertOk()->assertJsonPath('data.total', 1);

        $this->postJson(route('publico.visitas.store'), ['ruta' => '/registro', 'clave_visita' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('data.total', 1);

        $this->postJson(route('publico.visitas.store'), ['ruta' => '/', 'clave_visita' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('data.total', 2);

        $this->assertDatabaseCount('talento.evento_acceso', 3);
        $this->assertDatabaseHas('talento.evento_acceso', $inicio + ['usuario_id' => null, 'tipo' => 'VISITA']);
    }

    public function test_una_visita_autenticada_se_vincula_al_usuario_de_su_token(): void
    {
        $usuario = Usuario::factory()->create();
        $visita = ['ruta' => '/postulaciones/:id', 'clave_visita' => (string) Str::uuid()];

        $this->withToken($usuario->createToken('prueba-visitas')->plainTextToken)
            ->postJson(route('publico.visitas.store'), $visita + ['usuario_id' => 0])
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->assertDatabaseHas('talento.evento_acceso', $visita + ['usuario_id' => $usuario->id, 'tipo' => 'VISITA']);
    }

    public function test_se_validan_la_ruta_de_la_vista_y_la_clave_de_la_visita(): void
    {
        $this->postJson(route('publico.visitas.store'), ['ruta' => '/?q=ingenieria', 'clave_visita' => 'invalida'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ruta', 'clave_visita']);

        $this->assertDatabaseCount('talento.evento_acceso', 0);
    }

    public function test_acepta_rutas_de_empresa_con_parametro_de_proceso(): void
    {
        $ruta = '/empresa/solicitudes/:id/pago';

        $this->postJson(route('publico.visitas.store'), [
            'ruta' => $ruta,
            'clave_visita' => (string) Str::uuid(),
        ])
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }
}
