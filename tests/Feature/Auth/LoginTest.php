<?php

namespace Tests\Feature\Auth;

use App\Enums\CodigoRol;
use App\Enums\EstadoUsuario;
use App\Models\Carrera;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_devuelve_token_rol_y_perfil_y_reinicia_intentos(): void
    {
        $usuario = $this->crearEgresado(Carrera::factory()->create());
        $usuario->update(['correo' => 'ana@talento.test', 'intentos_fallidos' => 3]);

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'Ana@Talento.test', 'password' => 'password1']);

        $respuesta->assertOk()
            ->assertJsonPath('data.usuario.correo', 'ana@talento.test')
            ->assertJsonPath('data.usuario.rol.codigo', 'EGRESADO')
            ->assertJsonPath('data.usuario.persona.nombres', $usuario->persona->nombres)
            ->assertJsonCount(1, 'data.usuario.persona.egresados')
            ->assertJsonMissingPath('data.usuario.password_hash');
        $this->assertNotEmpty($respuesta->json('data.token'));
        $usuario->refresh();
        $this->assertSame(0, $usuario->intentos_fallidos);
        $this->assertNotNull($usuario->ultimo_acceso);
        $this->assertCount(1, $usuario->tokens);
    }

    public function test_el_login_es_unico_para_todos_los_roles(): void
    {
        Usuario::factory()->conRol(CodigoRol::Admin)->create(['correo' => 'admin@talento.test']);

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'admin@talento.test', 'password' => 'password1']);

        $respuesta->assertOk()->assertJsonPath('data.usuario.rol.codigo', 'ADMIN')->assertJsonPath('data.usuario.persona', null);
    }

    public function test_contrasena_incorrecta_responde_mensaje_generico_y_suma_un_intento(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@talento.test']);

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'ana@talento.test', 'password' => 'otra-clave']);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['correo' => 'El correo o la contraseña no son correctos.']);
        $this->assertSame(1, $usuario->refresh()->intentos_fallidos);
    }

    public function test_correo_inexistente_responde_el_mismo_mensaje_generico(): void
    {
        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'nadie@talento.test', 'password' => 'password1']);

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['correo' => 'El correo o la contraseña no son correctos.']);
    }

    public function test_el_quinto_intento_fallido_bloquea_la_cuenta_y_revoca_sus_tokens(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'ana@talento.test', 'intentos_fallidos' => 4]);
        $usuario->createToken('api');

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'ana@talento.test', 'password' => 'otra-clave']);

        $respuesta->assertStatus(423)->assertJsonPath('message', __('auth.blocked'));
        $usuario->refresh();
        $this->assertSame(EstadoUsuario::Bloqueado, $usuario->estado);
        $this->assertSame(5, $usuario->intentos_fallidos);
        $this->assertCount(0, $usuario->tokens);
    }

    public function test_cuenta_bloqueada_no_entra_aunque_la_contrasena_sea_correcta(): void
    {
        $usuario = Usuario::factory()->conEstado(EstadoUsuario::Bloqueado)->create(['correo' => 'ana@talento.test']);

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'ana@talento.test', 'password' => 'password1']);

        $respuesta->assertStatus(423);
        $this->assertCount(0, $usuario->tokens);
    }

    #[TestWith([EstadoUsuario::Pendiente])]
    #[TestWith([EstadoUsuario::Inactivo])]
    public function test_cuenta_no_activa_responde_403(EstadoUsuario $estado): void
    {
        Usuario::factory()->conEstado($estado)->create(['correo' => 'ana@talento.test']);

        $respuesta = $this->postJson(route('auth.login'), ['correo' => 'ana@talento.test', 'password' => 'password1']);

        $respuesta->assertForbidden()->assertJsonPath('message', __('auth.inactive'));
    }

    public function test_login_sin_datos_exige_correo_y_contrasena(): void
    {
        $respuesta = $this->postJson(route('auth.login'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'correo' => 'El campo correo es obligatorio.',
            'password' => 'El campo contraseña es obligatorio.',
        ]);
    }

    public function test_logout_revoca_el_token_actual(): void
    {
        $usuario = Usuario::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;

        $respuesta = $this->withToken($token)->postJson(route('auth.logout'));

        $respuesta->assertNoContent();
        $this->assertCount(0, $usuario->tokens()->get());
    }

    public function test_me_devuelve_el_usuario_autenticado(): void
    {
        $usuario = $this->crearEgresado(Carrera::factory()->create());

        $respuesta = $this->actingAs($usuario, 'sanctum')->getJson(route('auth.me'));

        $respuesta->assertOk()
            ->assertJsonPath('data.id', $usuario->id)
            ->assertJsonPath('data.persona.egresados.0.carrera.id', $usuario->persona->egresados->first()->carrera_id);
    }

    public function test_me_sin_token_responde_401(): void
    {
        $respuesta = $this->getJson(route('auth.me'));

        $respuesta->assertUnauthorized();
    }
}
