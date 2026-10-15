<?php

namespace Tests\Feature\Bd;

use App\Models\Carrera;
use App\Models\Persona;
use App\Models\Usuario;
use App\Support\ErroresBaseDatos;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Las violaciones que la validación no alcanza a detectar (concurrencia) nunca llegan como 500.
 */
class ErroresBaseDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_violacion_de_unico_mapeada_devuelve_422_con_su_campo_y_mensaje(): void
    {
        Usuario::factory()->create(['correo' => 'ana@talento.test']);

        $respuesta = $this->responder(fn () => Usuario::factory()->create(['correo' => 'ANA@talento.test']));

        $this->assertSame(422, $respuesta->getStatusCode());
        $this->assertSame(['correo' => ['El correo ya está registrado.']], $respuesta->getData(true)['errors']);
    }

    public function test_check_mapeado_devuelve_422(): void
    {
        $respuesta = $this->responder(fn () => Persona::factory()->create(['apellido_paterno' => null, 'apellido_materno' => null]));

        $this->assertSame(422, $respuesta->getStatusCode());
        $this->assertSame(['apellido_paterno' => ['Indica al menos un apellido.']], $respuesta->getData(true)['errors']);
    }

    public function test_mensaje_de_trigger_devuelve_422_con_el_texto_del_trigger(): void
    {
        $proceso = $this->crearConvocatoria(Carrera::factory()->create());
        $personaSinCarrera = Persona::factory()->create();

        $respuesta = $this->responder(fn () => DB::table('postulacion')->insert([
            'codigo' => 'T26-AAAAAA', 'proceso_id' => $proceso->id, 'persona_id' => $personaSinCarrera->id,
        ]));

        $this->assertSame(422, $respuesta->getStatusCode());
        $this->assertSame('La persona no es egresada de la carrera de esta convocatoria', $respuesta->getData(true)['message']);
    }

    public function test_borrar_un_registro_referenciado_devuelve_409(): void
    {
        $carrera = Carrera::factory()->create();

        $respuesta = $this->responder(fn () => DB::table('facultad')->where('id', $carrera->facultad_id)->delete());

        $this->assertSame(409, $respuesta->getStatusCode());
    }

    public function test_fuera_de_la_api_no_se_traduce(): void
    {
        Usuario::factory()->create(['correo' => 'ana@talento.test']);
        $excepcion = $this->capturar(fn () => Usuario::factory()->create(['correo' => 'ana@talento.test']));

        $respuesta = ErroresBaseDatos::responder($excepcion, Request::create('/'));

        $this->assertNull($respuesta);
    }

    private function responder(Closure $operacion): JsonResponse
    {
        return ErroresBaseDatos::responder($this->capturar($operacion), Request::create('/api/v1/prueba'));
    }

    private function capturar(Closure $operacion): QueryException
    {
        try {
            DB::transaction($operacion);
        } catch (QueryException $excepcion) {
            return $excepcion;
        }

        $this->fail('La operación debía violar una restricción de la BD.');
    }
}
