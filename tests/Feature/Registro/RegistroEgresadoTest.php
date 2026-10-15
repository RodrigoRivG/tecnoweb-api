<?php

namespace Tests\Feature\Registro;

use App\Enums\EstadoUsuario;
use App\Models\AreaPreferencia;
use App\Models\Carrera;
use App\Models\Egresado;
use App\Models\Facultad;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistroEgresadoTest extends TestCase
{
    use RefreshDatabase;

    private Carrera $carrera;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-14 15:00:00');
        $this->carrera = Carrera::factory()->create();
    }

    public function test_registro_crea_usuario_persona_egresado_y_consentimientos_y_devuelve_token(): void
    {
        $area = AreaPreferencia::factory()->for($this->carrera)->create();

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos([
            'correo' => 'Ana.Perez@Talento.test',
            'area_preferencia_id' => $area->id,
            'acepta_publicacion_perfil' => true,
        ]));

        $respuesta->assertCreated()
            ->assertJsonPath('data.usuario.correo', 'ana.perez@talento.test')
            ->assertJsonPath('data.usuario.estado', 'ACTIVO')
            ->assertJsonPath('data.usuario.rol.codigo', 'EGRESADO')
            ->assertJsonPath('data.usuario.persona.egresados.0.ppa', '72.50')
            ->assertJsonPath('data.usuario.persona.egresados.0.area_preferencia.id', $area->id);
        $this->assertNotEmpty($respuesta->json('data.token'));

        $usuario = Usuario::query()->where('correo', 'ana.perez@talento.test')->sole();
        $this->assertSame(EstadoUsuario::Activo, $usuario->estado);
        $this->assertTrue(Hash::check('clave1234', $usuario->password_hash));
        $this->assertDatabaseHas('persona', [
            'usuario_id' => $usuario->id,
            'nombres' => 'Ana',
            'ci_numero' => '7654321',
            'ci_complemento' => '1A',
            'correo_contacto' => 'ana.perez@talento.test',
        ]);
        $this->assertDatabaseHas('egresado', [
            'persona_id' => $usuario->persona->id,
            'carrera_id' => $this->carrera->id,
            'registro_universitario' => '218000001',
            'ppa' => '72.50',
        ]);
        $this->assertDatabaseHas('consentimiento', [
            'persona_id' => $usuario->persona->id, 'tipo' => 'TRATAMIENTO_DATOS', 'otorgado' => true, 'version_politica' => '1.0',
        ]);
        $this->assertDatabaseHas('consentimiento', [
            'persona_id' => $usuario->persona->id, 'tipo' => 'PUBLICACION_PERFIL', 'otorgado' => true,
        ]);
    }

    public function test_sin_aceptar_publicacion_se_registra_la_negativa(): void
    {
        $respuesta = $this->postJson(route('registro.egresado'), $this->datos());

        $respuesta->assertCreated();
        $this->assertDatabaseHas('consentimiento', ['tipo' => 'PUBLICACION_PERFIL', 'otorgado' => false]);
    }

    public function test_registro_sin_datos_exige_los_campos_obligatorios(): void
    {
        $respuesta = $this->postJson(route('registro.egresado'));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'correo', 'password', 'nombres', 'apellido_paterno', 'ci_numero', 'fecha_nacimiento',
            'carrera_id', 'registro_universitario', 'anio_egreso', 'ppa', 'acepta_tratamiento_datos',
        ]);
        $this->assertDatabaseCount('usuario', 0);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[DataProvider('datosInvalidos')]
    public function test_rechaza_datos_invalidos(array $cambios, string $campo, string $mensaje): void
    {
        $respuesta = $this->postJson(route('registro.egresado'), $this->datos($cambios));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([$campo => $mensaje]);
        $this->assertDatabaseCount('usuario', 0);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function datosInvalidos(): array
    {
        return [
            'sin apellidos' => [['apellido_paterno' => null, 'apellido_materno' => null], 'apellido_paterno', 'Indica al menos un apellido.'],
            'menor de edad' => [['fecha_nacimiento' => '2008-10-15'], 'fecha_nacimiento', 'Debes ser mayor de 18 años para registrarte.'],
            'sin consentimiento' => [['acepta_tratamiento_datos' => false], 'acepta_tratamiento_datos', 'Debes aceptar la política de tratamiento de datos.'],
            'contraseña sin números' => [['password' => 'soloLetras', 'password_confirmation' => 'soloLetras'], 'password', 'al menos un número'],
            'contraseña sin confirmar' => [['password_confirmation' => 'otra1234'], 'password', 'La confirmación de contraseña no coincide.'],
            'ppa fuera de rango' => [['ppa' => '100.50'], 'ppa', 'El campo PPA debe estar entre 0 y 100.'],
            'ppa con tres decimales' => [['ppa' => '70.555'], 'ppa', 'El PPA puede tener hasta 2 decimales.'],
            'ci con letras' => [['ci_numero' => '12AB45'], 'ci_numero', 'El formato de número de CI no es válido.'],
            'departamento inválido' => [['ci_expedido' => 'XX'], 'ci_expedido', 'El valor de departamento de expedición no es válido.'],
            'año de egreso futuro' => [['anio_egreso' => 2027], 'anio_egreso', 'El campo año de egreso debe estar entre 1950 y 2026.'],
        ];
    }

    public function test_area_de_otra_carrera_es_rechazada(): void
    {
        $areaAjena = AreaPreferencia::factory()->create();

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos(['area_preferencia_id' => $areaAjena->id]));

        $respuesta->assertUnprocessable()
            ->assertJsonValidationErrors(['area_preferencia_id' => 'El área de preferencia no pertenece a la carrera seleccionada.']);
    }

    public function test_carrera_inactiva_o_de_facultad_inactiva_es_rechazada(): void
    {
        $carreraDeFacultadInactiva = Carrera::factory()->for(Facultad::factory()->state(['activa' => false]))->create();

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos(['carrera_id' => $carreraDeFacultadInactiva->id]));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['carrera_id' => 'La carrera seleccionada no está disponible.']);
    }

    public function test_correo_ya_registrado_sin_importar_mayusculas_es_rechazado(): void
    {
        Usuario::factory()->create(['correo' => 'ana@talento.test']);

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos(['correo' => 'ANA@talento.test']));

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['correo' => 'El correo ya está registrado.']);
    }

    public function test_registro_universitario_duplicado_es_rechazado(): void
    {
        Egresado::factory()->create(['registro_universitario' => '218000001']);

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos());

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['registro_universitario' => 'El registro universitario ya está registrado.']);
    }

    public function test_ci_de_una_persona_con_cuenta_pide_iniciar_sesion(): void
    {
        Persona::factory()->create(['ci_numero' => '7654321', 'ci_complemento' => '1A']);

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos());

        $respuesta->assertUnprocessable()->assertJsonValidationErrors(['ci_numero' => 'Ya existe una cuenta con este CI. Inicia sesión.']);
    }

    public function test_ci_de_una_persona_sin_cuenta_no_se_vincula_y_pide_contactar_a_la_consultora(): void
    {
        $persona = Persona::factory()->sinUsuario()->create(['ci_numero' => '7654321', 'ci_complemento' => '1A']);

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos());

        $respuesta->assertUnprocessable()->assertJsonValidationErrors([
            'ci_numero' => 'Este CI ya está registrado en el sistema. Comunícate con la consultora para activar tu cuenta.',
        ]);
        $this->assertNull($persona->refresh()->usuario_id);
        $this->assertDatabaseCount('usuario', 0);
    }

    public function test_el_mismo_ci_con_otro_complemento_es_otra_persona(): void
    {
        Persona::factory()->create(['ci_numero' => '7654321', 'ci_complemento' => '']);

        $respuesta = $this->postJson(route('registro.egresado'), $this->datos());

        $respuesta->assertCreated();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return [
            'correo' => 'ana@talento.test',
            'password' => 'clave1234',
            'password_confirmation' => 'clave1234',
            'nombres' => 'Ana',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Rojas',
            'ci_numero' => '7654321',
            'ci_complemento' => '1a',
            'ci_expedido' => 'SC',
            'fecha_nacimiento' => '2000-05-20',
            'telefono' => '70000000',
            'carrera_id' => $this->carrera->id,
            'registro_universitario' => '218000001',
            'anio_egreso' => 2024,
            'ppa' => '72.50',
            'acepta_tratamiento_datos' => true,
            ...$cambios,
        ];
    }
}
