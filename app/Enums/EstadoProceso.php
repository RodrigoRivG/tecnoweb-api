<?php

namespace App\Enums;

enum EstadoProceso: string
{
    case Borrador = 'BORRADOR';
    case Configuracion = 'CONFIGURACION';
    case Abierto = 'ABIERTO';
    case EnEvaluacion = 'EN_EVALUACION';
    case Cerrado = 'CERRADO';
    case InformeEmitido = 'INFORME_EMITIDO';
    case Cancelado = 'CANCELADO';
}
