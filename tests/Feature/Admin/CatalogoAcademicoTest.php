<?php

namespace Tests\Feature\Admin;

use App\Enums\CodigoRol;
use App\Models\AreaPreferencia;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_crear_editar_y_eliminar_facultad(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();

        $crear = $this->actingAs($admin, 'sanctum')->postJson(route('admin.facultades.store'), [
            'nombre' => 'Facultad de Ciencias Exactas y Tecnología',
            'sigla' => 'fcet',
            'ppa_minimo' => 55.5,
            'activa' => true,
        ]);

        $crear->assertCreated()
            ->assertJsonPath('data.nombre', 'Facultad de Ciencias Exactas y Tecnología')
            ->assertJsonPath('data.sigla', 'FCET')
            ->assertJsonPath('data.ppa_minimo', '55.50')
            ->assertJsonPath('data.activa', true)
            ->assertJsonPath('data.carreras_count', 0);

        $facultadId = $crear->json('data.id');

        $listar = $this->actingAs($admin, 'sanctum')->getJson(route('admin.facultades.index'));
        $listar->assertOk()->assertJsonCount(1, 'data');

        $editar = $this->actingAs($admin, 'sanctum')->putJson(route('admin.facultades.update', $facultadId), [
            'nombre' => 'Facultad de Tecnología',
            'sigla' => 'FT',
            'ppa_minimo' => 60,
            'activa' => false,
        ]);

        $editar->assertOk()
            ->assertJsonPath('data.nombre', 'Facultad de Tecnología')
            ->assertJsonPath('data.sigla', 'FT')
            ->assertJsonPath('data.ppa_minimo', '60.00')
            ->assertJsonPath('data.activa', false);

        $eliminar = $this->actingAs($admin, 'sanctum')->deleteJson(route('admin.facultades.destroy', $facultadId));
        $eliminar->assertNoContent();
        $this->assertDatabaseMissing('facultad', ['id' => $facultadId]);
    }

    public function test_no_permite_eliminar_facultad_con_carreras_asociadas(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $facultad = Facultad::factory()->create();
        Carrera::factory()->for($facultad)->create();

        $respuesta = $this->actingAs($admin, 'sanctum')->deleteJson(route('admin.facultades.destroy', $facultad->id));

        $respuesta->assertStatus(409);
        $this->assertDatabaseHas('facultad', ['id' => $facultad->id]);
    }

    public function test_admin_puede_listar_crear_editar_con_areas_y_eliminar_carrera(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $facultad = Facultad::factory()->create(['nombre' => 'Ciencias Jurídicas', 'sigla' => 'FCJ']);

        $crear = $this->actingAs($admin, 'sanctum')->postJson(route('admin.carreras.store'), [
            'facultad_id' => $facultad->id,
            'nombre' => 'Derecho',
            'activa' => true,
            'areas' => ['Penal', 'Civil'],
        ]);

        $crear->assertCreated()
            ->assertJsonPath('data.nombre', 'Derecho')
            ->assertJsonPath('data.facultad.id', $facultad->id)
            ->assertJsonCount(2, 'data.areas');

        $carreraId = $crear->json('data.id');
        $areaCivilId = collect($crear->json('data.areas'))->firstWhere('nombre', 'Civil')['id'];

        $editar = $this->actingAs($admin, 'sanctum')->putJson(route('admin.carreras.update', $carreraId), [
            'facultad_id' => $facultad->id,
            'nombre' => 'Derecho y Ciencias Políticas',
            'activa' => true,
            'areas' => ['Civil', 'Laboral'],
        ]);

        $editar->assertOk()
            ->assertJsonPath('data.nombre', 'Derecho y Ciencias Políticas')
            ->assertJsonCount(2, 'data.areas');

        // Conserva el ID del área Civil que permaneció
        $this->assertDatabaseHas('area_preferencia', ['id' => $areaCivilId, 'nombre' => 'Civil']);
        $this->assertDatabaseMissing('area_preferencia', ['carrera_id' => $carreraId, 'nombre' => 'Penal']);
        $this->assertDatabaseHas('area_preferencia', ['carrera_id' => $carreraId, 'nombre' => 'Laboral']);

        $eliminar = $this->actingAs($admin, 'sanctum')->deleteJson(route('admin.carreras.destroy', $carreraId));
        $eliminar->assertNoContent();
        $this->assertDatabaseMissing('carrera', ['id' => $carreraId]);
    }

    public function test_egresado_no_puede_acceder_a_rutas_de_administracion(): void
    {
        $egresado = Usuario::factory()->conRol(CodigoRol::Egresado)->create();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('admin.facultades.index'))
            ->assertForbidden();

        $this->actingAs($egresado, 'sanctum')
            ->getJson(route('admin.carreras.index'))
            ->assertForbidden();
    }
}

