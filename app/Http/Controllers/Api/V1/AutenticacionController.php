<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IniciarSesion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutenticacionController extends Controller
{
    /**
     * Relaciones que devuelven el login, el registro y /auth/me.
     *
     * @var list<string>
     */
    public const RELACIONES_USUARIO = ['rol', 'persona.egresados.carrera.facultad', 'persona.egresados.areaPreferencia'];

    public function login(LoginRequest $request, IniciarSesion $iniciarSesion): JsonResponse
    {
        $usuario = $iniciarSesion->handle($request->validated('correo'), $request->validated('password'));

        return self::respuestaConToken($usuario, $request->validated('dispositivo'));
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user()->load(self::RELACIONES_USUARIO));
    }

    /**
     * Emite un token de Sanctum y devuelve { data: { token, usuario } }.
     */
    public static function respuestaConToken(Usuario $usuario, ?string $dispositivo, int $estado = 200): JsonResponse
    {
        $token = $usuario->createToken($dispositivo ?: 'api')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'usuario' => (new UsuarioResource($usuario->load(self::RELACIONES_USUARIO)))->resolve(),
            ],
        ], $estado);
    }
}
