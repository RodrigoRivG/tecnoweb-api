<?php

namespace App\Enums;

enum TipoConsentimiento: string
{
    case TratamientoDatos = 'TRATAMIENTO_DATOS';
    case PublicacionPerfil = 'PUBLICACION_PERFIL';
    case InvestigacionAntecedentes = 'INVESTIGACION_ANTECEDENTES';
}
