<?php

namespace Tests\Feature\Admin;

use App\Enums\CodigoRol;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Usuario;
use Database\Seeders\PermisoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_rol_nuevo_entra_al_panel_solo_con_los_permisos_marcados(): void
    {
        $this->seed(PermisoSeeder::class);

        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();

        $rol = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.roles.store'), [
                'codigo' => 'SECRETARIO',
                'nombre' => 'Secretario',
                'descripcion' => 'Apoyo administrativo',
                'es_panel' => true,
            ])
            ->assertCreated()
            ->json('data');

        $permiso = Permiso::query()->where('codigo', 'postulaciones.ver')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson(route('admin.roles.permisos.update', $rol['id']), [
                'permisos_ids' => [$permiso->id],
            ])
            ->assertOk();

        $creado = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.usuarios.store'), [
                'correo' => 'secretario@talento.test',
                'password' => 'secreto1',
                'rol_id' => $rol['id'],
                'nombres' => 'Ana',
                'apellido_paterno' => 'Rojas',
                'apellido_materno' => 'Paz',
                'ci_numero' => '5566778',
                'fecha_nacimiento' => '1998-04-12',
                'telefono' => '70011122',
            ])
            ->assertCreated();

        $usuarioId = $creado->json('data.id');

        $this->assertDatabaseHas(Persona::class, [
            'usuario_id' => $usuarioId,
            'ci_numero' => '5566778',
            'apellido_paterno' => 'Rojas',
        ]);

        $login = $this->postJson(route('auth.login'), [
            'correo' => 'secretario@talento.test',
            'password' => 'secreto1',
        ])->assertOk();

        $login->assertJsonPath('data.usuario.rol.es_panel', true)
            ->assertJsonPath('data.usuario.permisos.0', 'postulaciones.ver');

        $secretario = Usuario::query()->findOrFail($usuarioId);

        $this->actingAs($secretario, 'sanctum')
            ->getJson(route('evaluador.postulaciones.index'))
            ->assertOk();

        $this->actingAs($secretario, 'sanctum')
            ->getJson(route('admin.facultades.index'))
            ->assertForbidden();

        $this->actingAs($secretario, 'sanctum')
            ->getJson(route('evaluador.postulaciones.entrevista', 1))
            ->assertForbidden();
    }
}