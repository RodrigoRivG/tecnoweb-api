<?php

namespace App\Enums;

enum EstadoEtapa: string
{
    case Bloqueada = 'BLOQUEADA';
    case Pendiente = 'PENDIENTE';
    case EnCurso = 'EN_CURSO';
    case EnRevision = 'EN_REVISION';
    case Aprobada = 'APROBADA';
    case Reprobada = 'REPROBADA';
    case Omitida = 'OMITIDA';
}
