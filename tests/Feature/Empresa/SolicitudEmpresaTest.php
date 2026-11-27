<?php

namespace Tests\Feature\Empresa;

use App\Actions\Empresa\ConfirmarPagoEmpresa;
use App\Actions\Empresa\RegistrarCandidatosEmpresa;
use App\Enums\CodigoRol;
use App\Enums\EstadoProceso;
use App\Models\AsignacionPrueba;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudEmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_empresa_pago_y_candidatos(): void
    {
        config(['stripe.secret' => null, 'stripe.simular_sin_clave' => true]);

        $carrera = Carrera::factory()->create(['nombre' => 'Ingeniería de Sistemas']);
        $niveles = DimensionEvaluada::query()
            ->whereIn('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA'])
            ->pluck('id')
            ->map(fn ($id) => ['dimension_id' => $id, 'valor_ideal' => 7])
            ->all();

        $registro = $this->postJson(route('empresa.registro'), [
            'razon_social' => 'Norte Industrial',
            'nit' => '123456789',
            'correo_empresa' => 'oficina@norte.test',
            'telefono' => '70000000',
            'nombres' => 'Laura',
            'apellido_paterno' => 'Mendez',
            'ci_numero' => '555111',
            'fecha_nacimiento' => '1990-04-02',
            'correo_contacto' => 'laura@norte.test',
            'password' => 'Norte1234',
            'password_confirmation' => 'Norte1234',
        ])->assertCreated();

        $this->assertSame('EMPRESA', $registro->json('data.usuario.rol.codigo'));
        $procesoId = $registro->json('data.proceso_id');
        $this->assertNotNull($procesoId);

        $proceso = Proceso::query()->findOrFail($procesoId);
        $this->assertSame(EstadoProceso::PendientePago, $proceso->estado);

        $contacto = Usuario::query()->where('correo', 'laura@norte.test')->firstOrFail();
        $token = $registro->json('data.token');

        $this->withToken($token)
            ->postJson(route('empresa.solicitudes.datos', $proceso), [
                'cargo' => 'Analista de sistemas',
                'area' => 'Tecnología',
                'vacantes' => 2,
                'descripcion_funciones' => 'Apoyar la operación de los sistemas internos.',
                'carrera_id' => $carrera->id,
                'niveles' => $niveles,
            ])
            ->assertOk();

        $candidatos = $this->withToken($token)
            ->postJson(route('empresa.solicitudes.candidatos', $proceso), [
                'candidatos' => [[
                    'nombres' => 'Diego',
                    'apellido_paterno' => 'Rojas',
                    'ci_numero' => '888222',
                    'fecha_nacimiento' => '1998-01-15',
                    'correo' => 'diego.rojas@externo.test',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.pendiente_pago', true);

        $this->assertSame(EstadoProceso::PendientePago, $proceso->fresh()->estado);

        $this->withToken($token)
            ->postJson(route('empresa.solicitudes.simular-pago', $proceso))
            ->assertOk();

        $this->assertSame(EstadoProceso::EnCurso, $proceso->fresh()->estado);

        $resumen = $this->withToken($token)
            ->getJson(route('empresa.solicitudes.index'))
            ->assertOk()
            ->json('data.0');

        $clave = $resumen['accesos'][0]['clave_temporal'];
        $postulacionId = $resumen['accesos'][0]['postulacion_id'];
        $this->assertNotEmpty($clave);

        $this->postJson(route('auth.login'), [
            'correo' => 'diego.rojas@externo.test',
            'password' => $clave,
        ])->assertOk();

        $asignadas = AsignacionPrueba::query()->with('prueba')->where('postulacion_id', $postulacionId)->get();
        $this->assertGreaterThanOrEqual(1, $asignadas->count());

        $this->withToken($token)
            ->getJson(route('empresa.solicitudes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.accesos.0.correo', 'diego.rojas@externo.test');

        $this->withToken($token)
            ->getJson(route('empresa.postulaciones.informe', $postulacionId))
            ->assertOk();
    }
}