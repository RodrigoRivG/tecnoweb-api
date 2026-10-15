<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Registro\RegistrarEgresado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registro\RegistroEgresadoRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegistroEgresadoController extends Controller
{
    public function __invoke(RegistroEgresadoRequest $request, RegistrarEgresado $registrarEgresado): JsonResponse
    {
        $usuario = $registrarEgresado->handle($request->validated(), $request->ip());

        return AutenticacionController::respuestaConToken($usuario, null, Response::HTTP_CREATED);
    }
}
