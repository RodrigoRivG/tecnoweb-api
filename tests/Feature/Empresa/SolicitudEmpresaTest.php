<?php

namespace Tests\Feature\Empresa;

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

    public function test_la_empresa_carga_la_solicitud_y_el_admin_habilita_los_accesos(): void
    {
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
            'cargo' => 'Analista de sistemas',
            'area' => 'Tecnología',
            'vacantes' => 2,
            'descripcion_funciones' => 'Apoyar la operación de los sistemas internos.',
            'carrera_id' => $carrera->id,
            'niveles' => $niveles,
            'candidatos' => [[
                'nombres' => 'Diego',
                'apellido_paterno' => 'Rojas',
                'ci_numero' => '888222',
                'fecha_nacimiento' => '1998-01-15',
                'correo' => 'diego.rojas@externo.test',
            ]],
        ])->assertCreated();

        $this->assertSame('EMPRESA', $registro->json('data.usuario.rol.codigo'));
        $this->assertNull(Usuario::query()->where('correo', 'diego.rojas@externo.test')->first());

        $proceso = Proceso::query()->where('codigo', 'like', 'EMP-%')->firstOrFail();
        $this->assertSame(EstadoProceso::PendienteAprobacion, $proceso->estado);
        $this->getJson(route('publico.convocatorias'))
            ->assertOk()
            ->assertJsonMissing(['codigo' => $proceso->codigo]);

        $egresado = $this->crearEgresado($carrera);
        $this->actingAs($egresado, 'sanctum')->getJson(route('empresa.solicitudes.index'))->assertForbidden();

        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $aprobada = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.solicitudes-empresa.aprobar', $proceso))
            ->assertOk();

        $clave = $aprobada->json('data.accesos.0.clave_temporal');
        $postulacionId = $aprobada->json('data.accesos.0.postulacion_id');
        $this->assertNotEmpty($clave);
        $this->assertSame(EstadoProceso::EnCurso, $proceso->fresh()->estado);

        $candidato = Usuario::query()->where('correo', 'diego.rojas@externo.test')->firstOrFail();
        $this->assertSame(CodigoRol::CandidatoExterno->value, $candidato->rol->codigo instanceof \BackedEnum ? $candidato->rol->codigo->value : $candidato->rol->codigo);
        $this->assertGreaterThanOrEqual(4, AsignacionPrueba::query()->where('postulacion_id', $postulacionId)->count());

        $this->postJson(route('auth.login'), [
            'correo' => 'diego.rojas@externo.test',
            'password' => $clave,
        ])->assertOk();

        $contacto = Usuario::query()->where('correo', 'laura@norte.test')->firstOrFail();
        $this->actingAs($contacto, 'sanctum')
            ->getJson(route('empresa.solicitudes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.accesos.0.correo', 'diego.rojas@externo.test')
            ->assertJsonPath('data.0.accesos.0.clave_temporal', $clave);

        $this->actingAs($contacto, 'sanctum')
            ->getJson(route('empresa.postulaciones.informe', $postulacionId))
            ->assertOk()
            ->assertJsonStructure(['data' => ['perfil_personalidad', 'perfil_habilidades_blandas', 'resumen_puntajes']]);
    }
}
