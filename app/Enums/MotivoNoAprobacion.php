<?php

namespace App\Enums;

enum MotivoNoAprobacion: string
{
    case PuntajeBajo = 'PUNTAJE_BAJO';
    case TiempoMinimo = 'TIEMPO_MINIMO';
    case TiempoAgotado = 'TIEMPO_AGOTADO';
    case Otro = 'OTRO';
}
