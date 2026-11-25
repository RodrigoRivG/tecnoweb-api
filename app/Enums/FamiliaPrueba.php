<?php

namespace App\Enums;

enum FamiliaPrueba: string
{
    case Personalidad = 'PERSONALIDAD';
    case Inteligencia = 'INTELIGENCIA';
    case HabilidadBlanda = 'HABILIDAD_BLANDA';
    case HabilidadEspecifica = 'HABILIDAD_ESPECIFICA';

    /**
     * El banco completo rota en personalidad, inteligencia y habilidades blandas.
     * La habilidad específica rota dentro de los libros de la carrera.
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
