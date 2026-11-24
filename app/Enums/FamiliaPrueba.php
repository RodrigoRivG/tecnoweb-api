<?php

namespace App\Enums;

enum FamiliaPrueba: string
{
    case Personalidad = 'PERSONALIDAD';
    case Inteligencia = 'INTELIGENCIA';
    case HabilidadBlanda = 'HABILIDAD_BLANDA';
    case HabilidadEspecifica = 'HABILIDAD_ESPECIFICA';

    /**
     * Personalidad, inteligencia y habilidades blandas rotan de libro.
     * Las habilidades específicas quedan fijas por carrera.
     */
    public function alterna(): bool
    {
        return $this !== self::HabilidadEspecifica;
    }

    public function modo(): string
    {
        return $this->alterna() ? 'ALTERNA' : 'POR_CARRERA';
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Personalidad => 'Personalidad',
            self::Inteligencia => 'Inteligencia',
            self::HabilidadBlanda => 'Habilidades blandas',
            self::HabilidadEspecifica => 'Habilidades específicas',
        };
    }
}
