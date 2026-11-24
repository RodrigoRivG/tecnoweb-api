<?php

namespace Tests\Feature\Admin;

use App\Enums\CodigoRol;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Models\Facultad;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BancoGestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_crea_un_libro_que_rota_y_su_pregunta_exige_factor(): void
    {
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();
        $dimension = DimensionEvaluada::query()->where('tipo', 'PERSONALIDAD')->firstOrFail();

        $this->postJson(route('admin.pruebas.store'), [
            'nombre' => 'Libro de prueba',
            'familia' => 'PERSONALIDAD',
            'activo' => true,
        ])->assertUnauthorized();

        $creado = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.pruebas.store'), [
                'nombre' => 'Libro de prueba',
                'familia' => 'PERSONALIDAD',
                'activo' => true,
            ]);

        $creado->assertCreated()->assertJsonPath('data.activa', true);
        $libroId = $creado->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.preguntas.store'), [
                'prueba_id' => $libroId,
                'enunciado' => '¿Prefieres el silencio?',
                'opciones' => [
                    ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2],
                    ['etiqueta' => 'B', 'texto' => 'No', 'puntaje' => 0],
                ],
            ])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.preguntas.store'), [
                'prueba_id' => $libroId,
                'dimension_id' => $dimension->id,
                'enunciado' => '¿Prefieres el silencio?',
                'opciones' => [
                    ['etiqueta' => 'A', 'texto' => 'Sí', 'puntaje' => 2],
                    ['etiqueta' => 'B', 'texto' => 'No', 'puntaje' => 0],
                ],
            ])
            ->assertCreated();

        $this->assertTrue(
            Prueba::query()->whereKey($libroId)->where('activa', true)->where('familia', 'PERSONALIDAD')->whereHas('preguntas')->exists(),
        );
    }

    public function test_un_libro_especifico_activo_entra_en_la_convocatoria_de_su_carrera(): void
    {
        $carrera = Carrera::factory()->for(Facultad::factory()->state(['ppa_minimo' => '51.00']))->create();
        $proceso = $this->crearConvocatoria($carrera);
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.pruebas.store'), [
                'nombre' => 'Taller nuevo',
                'familia' => 'HABILIDAD_ESPECIFICA',
                'activo' => true,
                'carrera_ids' => [$carrera->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'TALLER_NUEVO');

        $libro = Prueba::query()->where('codigo', 'TALLER_NUEVO')->firstOrFail();
        $this->assertTrue($carrera->pruebas()->where('prueba.id', $libro->id)->exists());
        $this->assertTrue(
            ProcesoPrueba::query()
                ->where('prueba_id', $libro->id)
                ->whereHas('procesoEtapa', fn ($consulta) => $consulta->where('proceso_id', $proceso->id))
                ->exists(),
        );

        $this->actingAs($admin, 'sanctum')
            ->patchJson(route('admin.pruebas.update', ['prueba' => $libro->id]), [
                'nombre' => 'Taller nuevo',
                'codigo' => 'TALLER_NUEVO',
                'familia' => 'HABILIDAD_ESPECIFICA',
                'activo' => false,
                'carrera_ids' => [$carrera->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.activa', false);

        $this->assertFalse(
            ProcesoPrueba::query()->where('prueba_id', $libro->id)->exists(),
        );
    }
}
