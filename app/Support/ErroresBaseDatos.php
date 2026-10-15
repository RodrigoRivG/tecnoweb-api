<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Traduce las violaciones de restricciones de PostgreSQL a respuestas 422/409 (spec 02, sección 6.6).
 */
class ErroresBaseDatos
{
    private const UNICO = '23505';

    private const REFERENCIA = '23503';

    private const CHECK = '23514';

    private const TRIGGER = 'P0001';

    public static function responder(QueryException $excepcion, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        $estado = (string) ($excepcion->errorInfo[0] ?? $excepcion->getCode());
        $detalle = (string) ($excepcion->errorInfo[2] ?? $excepcion->getMessage());

        return match ($estado) {
            self::UNICO, self::CHECK, self::REFERENCIA => self::porRestriccion($estado, $detalle, $excepcion->getSql()),
            self::TRIGGER => self::respuesta('general', self::mensajeDelTrigger($detalle)),
            default => null,
        };
    }

    private static function porRestriccion(string $estado, string $detalle, string $sql): JsonResponse
    {
        $mapeo = __('bd.restricciones.'.self::nombreRestriccion($detalle));

        if (is_array($mapeo)) {
            return self::respuesta($mapeo['campo'], $mapeo['mensaje']);
        }

        // Borrar una fila que otras referencian
        if ($estado === self::REFERENCIA && preg_match('/^\s*delete\b/i', $sql) === 1) {
            return self::respuesta('general', __('bd.generico.en_uso'), 409);
        }

        return self::respuesta('general', __(match ($estado) {
            self::UNICO => 'bd.generico.unico',
            self::REFERENCIA => 'bd.generico.referencia',
            default => 'bd.generico.valor',
        }));
    }

    /**
     * Nombre de la restricción violada, sin depender del idioma del servidor:
     * "constraint "uq_x"" (inglés) o "restricción «uq_x»" (español).
     */
    private static function nombreRestriccion(string $detalle): string
    {
        preg_match_all('/["«]((?:uq|ck|fk)_[a-z0-9_]+)["»]/', $detalle, $coincidencias);

        return $coincidencias[1][0] ?? '';
    }

    /**
     * El mensaje de RAISE EXCEPTION llega como "ERROR:  <mensaje>\nCONTEXT: ...".
     */
    private static function mensajeDelTrigger(string $detalle): string
    {
        $primeraLinea = strtok($detalle, "\n") ?: $detalle;

        return trim(preg_replace('/^ERROR:\s*/', '', $primeraLinea));
    }

    private static function respuesta(string $campo, string $mensaje, int $estado = 422): JsonResponse
    {
        return response()->json(['message' => $mensaje, 'errors' => [$campo => [$mensaje]]], $estado);
    }
}
