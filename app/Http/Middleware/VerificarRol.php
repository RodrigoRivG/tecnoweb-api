<?php

namespace App\Http\Middleware;

use App\Enums\CodigoRol;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso solo a los roles indicados: ->middleware('rol:EGRESADO,ADMIN').
 */
class VerificarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user();

        abort_unless(
            $usuario?->tieneRol(...array_map(CodigoRol::from(...), $roles)),
            Response::HTTP_FORBIDDEN,
            __('talento.rol_no_autorizado'),
        );

        return $next($request);
    }
}
