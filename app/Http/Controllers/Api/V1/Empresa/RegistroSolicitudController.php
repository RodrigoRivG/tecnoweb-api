<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\RegistrarSolicitudEmpresa;
use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Support\ReglasValidacionCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistroSolicitudController extends Controller
{
    public function catalogo(): JsonResponse
    {
        $carreras = Carrera::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre']);
        $personalidad = DimensionEvaluada::query()->where('tipo', 'PERSONALIDAD')->orderBy('orden')->get(['id', 'factor', 'nombre']);
        $blandas = DimensionEvaluada::query()->where('tipo', 'HABILIDAD_BLANDA')->orderBy('orden')->get(['id', 'codigo', 'nombre']);

        return response()->json([
            'data' => [
                'carreras' => $carreras,
                'personalidad' => $personalidad->map(fn ($dimension) => [
                    'dimension_id' => $dimension->id,
                    'codigo' => $dimension->factor,
                    'nombre' => $dimension->nombre,
                ]),
                'habilidades_blandas' => $blandas->map(fn ($dimension) => [
                    'dimension_id' => $dimension->id,
                    'codigo' => $dimension->codigo,
                    'nombre' => $dimension->nombre,
                ]),
            ],
        ]);
    }

    public function store(Request $request, RegistrarSolicitudEmpresa $registrar): JsonResponse
    {
        $datos = $request->validate(
            [
                'razon_social' => ['required', 'string', 'max:200'],
                'nit' => ReglasValidacionCuenta::nitBolivia(),
                'correo_empresa' => ['required', 'email', 'max:190', 'different:correo_contacto'],
                'telefono' => ReglasValidacionCuenta::telefonoBolivia(),
                'nombres' => ['required', 'string', 'max:100'],
                'apellido_paterno' => ['required', 'string', 'max:60'],
                'apellido_materno' => ['nullable', 'string', 'max:60'],
                'ci_numero' => ReglasValidacionCuenta::ciNumero(),
                'ci_expedido' => ReglasValidacionCuenta::ciExpedidoBolivia(),
                'fecha_nacimiento' => ReglasValidacionCuenta::fechaMayoriaDeEdad(),
                'correo_contacto' => ['required', 'email', 'max:190'],
                'password' => ReglasValidacionCuenta::passwordAlta(),
                'cargo_contacto' => ['nullable', 'string', 'max:100'],
            ],
            ReglasValidacionCuenta::mensajes(),
        );

        $resultado = $registrar->handle($datos, $request->ip());
        $usuario = $resultado['usuario'];
        $proceso = $resultado['proceso'];

        $respuesta = AutenticacionController::respuestaConToken($usuario, null, 201);
        $carga = $respuesta->getData(true);
        $carga['data']['proceso_id'] = $proceso->id;
        $carga['data']['proceso_estado'] = $proceso->estado->value;

        return response()->json($carga, 201);
    }
}
