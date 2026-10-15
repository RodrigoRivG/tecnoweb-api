<?php

namespace App\Enums;

enum EstadoDocumento: string
{
    case Pendiente = 'PENDIENTE';
    case Validado = 'VALIDADO';
    case Observado = 'OBSERVADO';
    case Rechazado = 'RECHAZADO';
}
