<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RegistrarIntentoLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if ($respuesta->isSuccessful() || $respuesta->isClientError()) {
            try {
                DB::table('talento.evento_acceso')->insert([
                    'tipo' => $respuesta->isSuccessful() ? 'LOGIN_EXITOSO' : 'LOGIN_FALLIDO',
                    'usuario_id' => $this->identificarUsuario($request),
                    'direccion_ip' => $request->ip(),
                ]);
            } catch (QueryException $error) {
                report($error);
            }
        }

        return $respuesta;
    }

    private function identificarUsuario(Request $request): ?int
    {
        $correo = $request->input('correo');

        if (! is_string($correo) || trim($correo) === '' || mb_strlen($correo) > 190) {
            return null;
        }

        return Usuario::query()->whereRaw('lower(correo) = ?', [Str::lower(trim($correo))])->value('id');
    }
}
