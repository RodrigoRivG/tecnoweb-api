<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso solo a usuarios cuyo rol tenga al menos uno de los permisos indicados:
 * ->middleware('permiso:postulaciones.ver,cv.calificar')
 */
class VerificarPermiso
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user();

        abort_unless(
            $usuario && $usuario->tienePermiso(...$permisos),
            Response::HTTP_FORBIDDEN,
            'No cuentas con los permisos necesarios para realizar esta acción.',
        );

        return $next($request);
    }
}

