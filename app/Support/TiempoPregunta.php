<?php

namespace App\Support;

use App\Enums\FamiliaPrueba;
use App\Models\ConfiguracionFamilia;
use DateTimeInterface;

/**
 * Segundos que dura cada pregunta, según la familia del libro.
 */
final class TiempoPregunta
{
    public const TOLERANCIA_SEG = 2;

    public static function segundos(?FamiliaPrueba $familia): int
    {
        if ($familia === null) {
            return 60;
        }

        return (int) (ConfiguracionFamilia::query()->where('familia', $familia->value)->value('tiempo_pregunta_seg') ?? 60);
    }

    public static function transcurridos(?DateTimeInterface $inicio): int
    {
        if ($inicio === null) {
            return 0;
        }

        return (int) abs(now()->diffInSeconds($inicio));
    }

    public static function restantes(?DateTimeInterface $inicio, int $limite): int
    {
        return max(0, $limite - self::transcurridos($inicio));
    }

    public static function vencido(?DateTimeInterface $inicio, int $limite, bool $conTolerancia = false): bool
    {
        if ($inicio === null) {
            return false;
        }

        $margen = $conTolerancia ? self::TOLERANCIA_SEG : 0;

        return self::transcurridos($inicio) > ($limite + $margen);
    }
}
