<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case Pendiente = 'PENDIENTE';
    case Activo = 'ACTIVO';
    case Bloqueado = 'BLOQUEADO';
    case Inactivo = 'INACTIVO';
}
