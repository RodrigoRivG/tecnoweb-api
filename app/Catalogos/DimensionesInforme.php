<?php

namespace App\Catalogos;

/**
 * Catálogo fijo del informe: 16 factores del 16PF y dimensiones socioemocionales.
 * Los códigos internos de personalidad se conservan porque el banco de preguntas ya los usa.
 */
final class DimensionesInforme
{
    /**
     * @return list<array{codigo: string, factor: string, orden: int, nombre: string, polo_bajo: string, polo_alto: string}>
     */
    public static function factores16Pf(): array
    {
        return [
            self::factor('SOCIABILIDAD', 'A', 1, 'Sociabilidad', 'Reservado / Distante', 'Sociable / Afectuoso'),
            self::factor('CAPACIDAD_ESCOLAR', 'B', 2, 'Capacidad escolar', 'Pensamiento concreto', 'Pensamiento abstracto'),
            self::factor('ESTABILIDAD_EMOCIONAL', 'C', 3, 'Estabilidad emocional', 'Emocionalmente reactivo', 'Estable / Maduro'),
            self::factor('SUMISION', 'E', 4, 'Dominancia', 'Sumiso / Dócil', 'Dominante / Asertivo'),
            self::factor('RETRAIMIENTO', 'F', 5, 'Animación', 'Prudente / Cauto', 'Entusiasta / Dinámico'),
            self::factor('SUPEREGO', 'G', 6, 'Atención a las normas', 'Flexible con reglas', 'Concienzudo / Riguroso'),
            self::factor('TIMIDEZ', 'H', 7, 'Atrevimiento', 'Tímido / Cohibido', 'Atrevido / Emprendedor'),
            self::factor('SEVERIDAD', 'I', 8, 'Sensibilidad', 'Sensible / Empático', 'Racional / Pragmático'),
            self::factor('CONFIANZA', 'L', 9, 'Vigilancia', 'Confiado / Adaptable', 'Suspicaz / Escéptico'),
            self::factor('OBJETIVIDAD', 'M', 10, 'Abstracción', 'Práctico / Realista', 'Imaginativo / Creativo'),
            self::factor('INGENUIDAD', 'N', 11, 'Privacidad', 'Sencillo / Espontáneo', 'Astuto / Diplomático'),
            self::factor('SEGURIDAD_DE_SI_MISMO', 'O', 12, 'Aprensión', 'Seguro / Imperturbable', 'Aprensivo / Autocrítico'),
            self::factor('CONSERVADURISMO', 'Q1', 13, 'Apertura al cambio', 'Tradicional / Clásico', 'Innovador / Abierto al cambio'),
            self::factor('DEPENDENCIA_GRUPAL', 'Q2', 14, 'Autosuficiencia', 'Orientado al grupo', 'Autónomo / Autosuficiente'),
            self::factor('INDIFERENCIA', 'Q3', 15, 'Perfeccionismo', 'Tolerante / Despreocupado', 'Perfeccionista / Disciplinado'),
            self::factor('TRANQUILIDAD', 'Q4', 16, 'Tensión', 'Relajado / Plácido', 'Tenso / Enérgico'),
        ];
    }

    /**
     * Dimensiones que el banco ya puede medir. El cuadro 2 del informe las compara en estenes 1–10.
     *
     * @return list<array{codigo: string, nombre: string, orden: int}>
     */
    public static function habilidadesBlandas(): array
    {
        return [
            ['codigo' => 'ATENCION_EMOCIONAL', 'nombre' => 'Atención emocional', 'orden' => 17],
            ['codigo' => 'CLARIDAD_EMOCIONAL', 'nombre' => 'Claridad emocional', 'orden' => 18],
            ['codigo' => 'REPARACION_EMOCIONAL', 'nombre' => 'Reparación emocional', 'orden' => 19],
            ['codigo' => 'COMUNICACION_ASERTIVA', 'nombre' => 'Comunicación asertiva', 'orden' => 20],
            ['codigo' => 'TRABAJO_EN_EQUIPO', 'nombre' => 'Trabajo en equipo', 'orden' => 21],
            ['codigo' => 'RESOLUCION_CONFLICTOS', 'nombre' => 'Resolución de conflictos', 'orden' => 22],
            ['codigo' => 'ADAPTABILIDAD', 'nombre' => 'Adaptabilidad', 'orden' => 23],
            ['codigo' => 'LIDERAZGO_INICIATIVA', 'nombre' => 'Liderazgo', 'orden' => 24],
        ];
    }

    /**
     * @return array{codigo: string, factor: string, orden: int, nombre: string, polo_bajo: string, polo_alto: string}
     */
    private static function factor(string $codigo, string $factor, int $orden, string $nombre, string $poloBajo, string $poloAlto): array
    {
        return [
            'codigo' => $codigo,
            'factor' => $factor,
            'orden' => $orden,
            'nombre' => $nombre,
            'polo_bajo' => $poloBajo,
            'polo_alto' => $poloAlto,
        ];
    }
}
