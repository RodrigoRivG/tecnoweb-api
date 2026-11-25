<?php

namespace App\Catalogos;

/**
 * Libros del banco psicotécnico: 4 familias, sin recorte de ítems.
 */
final class LibrosPrueba
{
    /**
     * @return list<array{codigo: string, nombre: string, familia: string, tiempo_limite_seg: int, descripcion: string}>
     */
    public static function definiciones(): array
    {
        return [
            self::libro('16PF_FORMA_A', '16PF Forma A', 'PERSONALIDAD', 3000, 'Cuestionario de 16 factores, forma A. Se rinden las 187 preguntas.'),
            self::libro('16PF_FORMA_B', '16PF Forma B', 'PERSONALIDAD', 3000, 'Forma paralela. Las 187 preguntas apuntan a los mismos factores A–Q4.'),
            self::libro('ES_USTED_MUY_HABIL', '¿Es usted muy hábil?', 'INTELIGENCIA', 1200, 'Matrices de figuras. Se rinden las 15 preguntas.'),
            self::libro('DOMINOS_D48', 'Dominós D48', 'INTELIGENCIA', 1500, 'Series de fichas. Se rinden las 48 preguntas.'),
            self::libro('RAVEN', 'Matrices progresivas', 'INTELIGENCIA', 2400, 'Matrices en 5 series. Se rinden los 60 ítems.'),
            self::libro('TMMS_24', 'TMMS-24', 'HABILIDAD_BLANDA', 600, 'Atención, claridad y reparación emocional. 24 preguntas.'),
            self::libro('TEST_HABILIDADES_BLANDAS', 'Habilidades blandas', 'HABILIDAD_BLANDA', 900, 'Comunicación, equipo, conflictos, adaptabilidad y liderazgo. 25 preguntas.'),
            self::libro('TEST_LECTURA_COMPRENSIVA', 'Lectura comprensiva', 'HABILIDAD_ESPECIFICA', 1500, 'Análisis de textos técnicos.'),
            self::libro('TEST_PURDUE_NO_VERBAL', 'Purdue no verbal', 'HABILIDAD_ESPECIFICA', 1500, 'Aptitud espacial. 48 series.'),
            self::libro('TEST_APTITUD_MECANICA', 'Aptitud mecánica', 'HABILIDAD_ESPECIFICA', 1200, 'Palancas, engranajes y equilibrio.'),
            self::libro('TEST_CODIFICACION', 'Codificación', 'HABILIDAD_ESPECIFICA', 1500, 'Seguimiento de instrucciones y condiciones.'),
            self::libro('TEST_LABERINTOS_VISOESPACIAL', 'Laberintos visoespaciales', 'HABILIDAD_ESPECIFICA', 1200, 'Recorridos y orientación espacial.'),
        ];
    }

    /**
     * Libros por defecto de la carrera. Se rinde uno, rotando dentro de este grupo.
     *
     * @return list<string>
     */
    public static function trioPorCarrera(string $nombre): array
    {
        $texto = mb_strtolower($nombre);

        if (self::contiene($texto, ['informática', 'informatica', 'sistemas', 'redes', 'telecom', 'software', 'computación', 'computacion'])) {
            return ['TEST_PURDUE_NO_VERBAL', 'TEST_LABERINTOS_VISOESPACIAL', 'TEST_LECTURA_COMPRENSIVA'];
        }

        if (self::contiene($texto, ['civil'])) {
            return ['TEST_APTITUD_MECANICA', 'TEST_LABERINTOS_VISOESPACIAL', 'TEST_PURDUE_NO_VERBAL'];
        }

        if (self::contiene($texto, ['mecánica', 'mecanica', 'industrial', 'eléctrica', 'electrica', 'electrónica', 'electronica', 'petróleo', 'petroleo', 'química', 'quimica'])) {
            return ['TEST_APTITUD_MECANICA', 'TEST_PURDUE_NO_VERBAL', 'TEST_LABERINTOS_VISOESPACIAL'];
        }

        return ['TEST_LECTURA_COMPRENSIVA', 'TEST_CODIFICACION', 'TEST_LABERINTOS_VISOESPACIAL'];
    }

    /**
     * @return list<array{codigo: string, peso: float, modo: string}>
     */
    public static function cuposRotativos(): array
    {
        return [
            ['codigo' => '16PF_FORMA_A', 'peso' => 10.0, 'modo' => 'ALTERNA'],
            ['codigo' => 'ES_USTED_MUY_HABIL', 'peso' => 8.0, 'modo' => 'ALTERNA'],
            ['codigo' => 'TMMS_24', 'peso' => 12.0, 'modo' => 'ALTERNA'],
        ];
    }

    /**
     * @param  list<string>  $frases
     */
    private static function contiene(string $texto, array $frases): bool
    {
        foreach ($frases as $frase) {
            if (str_contains($texto, $frase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{codigo: string, nombre: string, familia: string, tiempo_limite_seg: int, descripcion: string}
     */
    private static function libro(string $codigo, string $nombre, string $familia, int $tiempo, string $descripcion): array
    {
        return [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'familia' => $familia,
            'tiempo_limite_seg' => $tiempo,
            'descripcion' => $descripcion,
        ];
    }
}
