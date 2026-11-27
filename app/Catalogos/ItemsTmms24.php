<?php

namespace App\Catalogos;

/**
 * Ítems del TMMS-24 integrados en el test único de habilidades blandas.
 *
 * @return list<array{dimension: string, texto: string}>
 */
final class ItemsTmms24
{
    /**
     * @return list<array{dimension: string, texto: string}>
     */
    public static function afirmaciones(): array
    {
        return [
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Presto mucha atención a los sentimientos.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Normalmente me preocupo mucho por lo que siento.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Normalmente dedico tiempo a pensar en mis emociones.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Pienso que merece la pena prestar atención a mis emociones y estado de ánimo.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Dejo que mis sentimientos afecten a mis pensamientos.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Pienso en mi estado de ánimo constantemente.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'A menudo pienso en mis sentimientos.'],
            ['dimension' => 'ATENCION_EMOCIONAL', 'texto' => 'Presto mucha atención a cómo me siento.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Tengo claros mis sentimientos.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Frecuentemente puedo definir mis sentimientos.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Casi siempre sé cómo me siento.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Normalmente conozco mis sentimientos sobre las personas.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'A menudo me doy cuenta de mis sentimientos en diferentes situaciones.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Siempre puedo decir cómo me siento.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'A veces puedo decir cuáles son mis emociones.'],
            ['dimension' => 'CLARIDAD_EMOCIONAL', 'texto' => 'Puedo llegar a comprender mis sentimientos.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Aunque a veces me siento triste, suelo tener una visión optimista.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Aunque me sienta mal, procuro pensar en cosas agradables.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Cuando estoy triste, pienso en todos los placeres de la vida.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Intento tener pensamientos positivos aunque me sienta mal.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Si doy demasiadas vueltas a las cosas, complicándolas, trato de calmarme.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Me preocupo por tener un buen estado de ánimo.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Tengo mucha energía cuando me siento feliz.'],
            ['dimension' => 'REPARACION_EMOCIONAL', 'texto' => 'Cuando estoy enfadado intento cambiar mi estado de ánimo.'],
        ];
    }
}