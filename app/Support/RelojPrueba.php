<?php

namespace App\Support;

use App\Models\AsignacionPrueba;
use App\Models\ProcesoPrueba;

/**
 * El reloj de un cupo usa el tiempo del libro asignado a esa postulación.
 */
final class RelojPrueba
{
    public static function segundosMaximos(ProcesoPrueba $cupo, int $postulacionId): ?int
    {
        $cupo->loadMissing('prueba');

        $asignada = AsignacionPrueba::query()
            ->with('prueba')
            ->where('postulacion_id', $postulacionId)
            ->where('proceso_prueba_id', $cupo->id)
            ->first()
            ?->prueba;

        $segundos = $asignada?->tiempo_limite_seg
            ?? $cupo->prueba?->tiempo_limite_seg
            ?? $cupo->tiempo_maximo_seg;

        return $segundos !== null ? (int) $segundos : null;
    }
}
