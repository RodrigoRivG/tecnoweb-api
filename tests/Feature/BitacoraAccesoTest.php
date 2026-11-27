<?php

namespace Tests\Feature;

use App\Enums\CodigoRol;
use App\Models\Permiso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BitacoraAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_guardan_accesos_exitosos_y_fallidos_con_el_usuario_cuando_se_identifica(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'persona@talento.test']);

        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::42'])
            ->postJson(route('auth.login'), ['correo' => 'Persona@Talento.test', 'password' => 'password1'])
            ->assertOk()
            ->assertJsonPath('data.usuario.id', $usuario->id);

        $this->postJson(route('auth.login'), ['correo' => $usuario->correo, 'password' => 'incorrecta'])
            ->assertUnprocessable();

        $this->postJson(route('auth.login'), ['correo' => 'desconocido@talento.test', 'password' => 'incorrecta'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('talento.evento_acceso', 3);
        $this->assertDatabaseHas('talento.evento_acceso', [
            'tipo' => 'LOGIN_EXITOSO',
            'usuario_id' => $usuario->id,
            'direccion_ip' => '2001:db8::42',
        ]);
        $this->assertDatabaseHas('talento.evento_acceso', ['tipo' => 'LOGIN_FALLIDO', 'usuario_id' => $usuario->id]);
        $this->assertDatabaseHas('talento.evento_acceso', ['tipo' => 'LOGIN_FALLIDO', 'usuario_id' => null]);
    }

    public function test_los_rechazos_de_validacion_del_login_se_registran(): void
    {
        $this->postJson(route('auth.login'))->assertUnprocessable();

        $this->assertDatabaseHas('talento.evento_acceso', ['tipo' => 'LOGIN_FALLIDO', 'usuario_id' => null]);
    }

    public function test_la_bitacora_requiere_sesion_y_el_permiso_correspondiente(): void
    {
        $this->getJson(route('admin.bitacora'))->assertUnauthorized();

        $ayudante = Usuario::factory()->conRol(CodigoRol::Ayudante)->create();
        $this->actingAs($ayudante, 'sanctum')->getJson(route('admin.bitacora'))->assertForbidden();

        $permiso = Permiso::query()->where('codigo', 'bitacora.ver')->firstOrFail();
        $ayudante->rol->permisos()->attach($permiso->id);
        $this->actingAs($ayudante->fresh(), 'sanctum')->getJson(route('admin.bitacora'))->assertOk();
    }

    public function test_la_bitacora_filtra_por_resultado_y_dia_en_la_zona_horaria_del_negocio(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $eventos = DB::table('talento.evento_acceso');

        foreach ([
            ['LOGIN_EXITOSO', '2026-11-27 01:00:00-04'],
            ['LOGIN_FALLIDO', '2026-11-27 23:45:00-04'],
            ['LOGIN_FALLIDO', '2026-11-28 00:00:00-04'],
        ] as [$tipo, $fecha]) {
            $eventos->insert([
                'tipo' => $tipo, 'created_at' => $fecha, 'usuario_id' => $admin->id, 'direccion_ip' => '192.0.2.4',
            ]);
        }

        $eventos->insert(['tipo' => 'VISITA', 'ruta' => '/', 'clave_visita' => (string) Str::uuid()]);

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('admin.bitacora'))
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->getJson(route('admin.bitacora', [
            'resultado' => 'LOGIN_FALLIDO', 'desde' => '2026-11-27', 'hasta' => '2026-11-27',
        ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo', 'LOGIN_FALLIDO')
            ->assertJsonPath('data.0.correo', $admin->correo)
            ->assertJsonPath('data.0.direccion_ip', '192.0.2.4');

        $this->getJson(route('admin.bitacora', ['desde' => '2026-11-28', 'hasta' => '2026-11-27']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hasta');
    }
}
