<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\RegistrarSolicitudEmpresa;
use App\Http\Controllers\Api\V1\AutenticacionController;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
        $datos = $request->validate([
            'razon_social' => ['required', 'string', 'max:200'],
            'nit' => ['required', 'string', 'max:20'],
            'correo_empresa' => ['required', 'email', 'max:190'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:60'],
            'apellido_materno' => ['nullable', 'string', 'max:60'],
            'ci_numero' => ['required', 'string', 'max:15'],
            'ci_expedido' => ['nullable', Rule::in(['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'])],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'correo_contacto' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'cargo_contacto' => ['nullable', 'string', 'max:100'],
        ]);

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
