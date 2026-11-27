<?php

namespace Tests\Feature;

use App\Enums\CodigoRol;
use App\Models\Carrera;
use App\Models\Permiso;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_actividad_y_paginas_mas_visitadas_en_el_periodo_local(): void
    {
        config(['app.zona_horaria_negocio' => 'America/La_Paz']);
        $this->travelTo(CarbonImmutable::parse('2026-11-27 16:00:00-04'));
        $admin = Usuario::factory()->conRol(CodigoRol::Admin)->create();

        foreach ([
            ['VISITA', '/', '2026-11-20 23:59:59-04'],
            ['VISITA', '/', '2026-11-21 00:00:00-04'],
            ['VISITA', '/', '2026-11-27 09:00:00-04'],
            ['VISITA', '/panel/facultades', '2026-11-27 10:00:00-04'],
            ['LOGIN_EXITOSO', null, '2026-11-26 23:59:59-04'],
            ['LOGIN_FALLIDO', null, '2026-11-27 08:00:00-04'],
            ['VISITA', '/panel/facultades', '2026-11-28 00:00:00-04'],
        ] as [$tipo, $ruta, $fecha]) {
            DB::table('talento.evento_acceso')->insert([
                'tipo' => $tipo,
                'ruta' => $ruta,
                'clave_visita' => $ruta === null ? null : (string) Str::uuid(),
                'created_at' => $fecha,
            ]);
        }

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('admin.estadisticas', ['dias' => 7]))
            ->assertOk()
            ->assertJsonPath('data.periodo', ['dias' => 7, 'desde' => '2026-11-21', 'hasta' => '2026-11-27'])
            ->assertJsonPath('data.totales', ['visitas' => 3, 'accesos_exitosos' => 1, 'accesos_fallidos' => 1])
            ->assertJsonCount(7, 'data.actividad')
            ->assertJsonPath('data.actividad.1', ['fecha' => '2026-11-22', 'visitas' => 0, 'accesos_exitosos' => 0, 'accesos_fallidos' => 0])
            ->assertJsonPath('data.actividad.5', ['fecha' => '2026-11-26', 'visitas' => 0, 'accesos_exitosos' => 1, 'accesos_fallidos' => 0])
            ->assertJsonPath('data.paginas', [['ruta' => '/', 'visitas' => 2], ['ruta' => '/panel/facultades', 'visitas' => 1]]);
    }

    public function test_muestra_el_estado_actual_de_las_postulaciones_por_etapa(): void
    {
        $carrera = Carrera::factory()->create();
        $proceso = $this->crearConvocatoria($carrera);
        $egresado = $this->crearEgresado($carrera, '85.00');

        $this->actingAs($egresado, 'sanctum')
            ->postJson(route('egresado.postulaciones.store'), ['proceso_id' => $proceso->id])
            ->assertCreated();

        $respuesta = $this->actingAs(Usuario::factory()->conRol(CodigoRol::Admin)->create(), 'sanctum')
            ->getJson(route('admin.estadisticas'))
            ->assertOk()
            ->assertJsonPath('data.periodo.dias', 30)
            ->assertJsonPath('data.totales.visitas', 0)
            ->assertJsonPath('data.postulaciones.0.etapa', 'INSCRIPCION')
            ->assertJsonPath('data.postulaciones.0.aprobadas', 1);

        $this->assertNotEmpty($respuesta->json('data.postulaciones'));
    }

    public function test_se_exige_permiso_y_se_valida_el_periodo(): void
    {
        $this->getJson(route('admin.estadisticas'))->assertUnauthorized();

        $ayudante = Usuario::factory()->conRol(CodigoRol::Ayudante)->create();
        $this->actingAs($ayudante, 'sanctum')->getJson(route('admin.estadisticas'))->assertForbidden();

        $ayudante->rol->permisos()->attach(Permiso::query()->where('codigo', 'estadisticas.ver')->firstOrFail()->id);

        $this->actingAs($ayudante->fresh(), 'sanctum')
            ->getJson(route('admin.estadisticas', ['dias' => 90]))
            ->assertOk()
            ->assertJsonCount(90, 'data.actividad');

        $this->getJson(route('admin.estadisticas', ['dias' => 365]))->assertUnprocessable()->assertJsonValidationErrors('dias');
    }
}
