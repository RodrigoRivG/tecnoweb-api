<?php

namespace App\Enums;

enum EstadoPostulacion: string
{
    case Inscrita = 'INSCRITA';
    case EnProceso = 'EN_PROCESO';
    case Aprobada = 'APROBADA';
    case NoAprobada = 'NO_APROBADA';
    case Retirada = 'RETIRADA';
}
