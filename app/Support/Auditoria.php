<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Ejecuta escrituras en una transacción que identifica al usuario ante los triggers de historial.
 */
class Auditoria
{
    /**
     * @template TResultado
     *
     * @param  Closure(): TResultado  $operacion
     * @return TResultado
     */
    public static function comoUsuario(int $usuarioId, Closure $operacion): mixed
    {
        return DB::transaction(function () use ($usuarioId, $operacion): mixed {
            // Equivale a SET LOCAL app.usuario_id: vale solo dentro de esta transacción
            DB::select("SELECT set_config('app.usuario_id', ?, true)", [(string) $usuarioId]);

            return $operacion();
        });
    }
}
