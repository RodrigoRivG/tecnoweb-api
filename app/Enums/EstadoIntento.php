<?php

namespace App\Enums;

enum EstadoIntento: string
{
    case EnCurso = 'EN_CURSO';
    case Finalizado = 'FINALIZADO';
    case Expirado = 'EXPIRADO';
    case Anulado = 'ANULADO';
}

