<?php

namespace App\Enums;

enum EstadoProceso: string
{
    case Borrador = 'BORRADOR';
    case Configuracion = 'CONFIGURACION';
    case PendienteAprobacion = 'PENDIENTE_APROBACION';
    case PendientePago = 'PENDIENTE_PAGO';
    case PendienteCandidatos = 'PENDIENTE_CANDIDATOS';
    case EnCurso = 'EN_CURSO';
    case Abierto = 'ABIERTO';
    case EnEvaluacion = 'EN_EVALUACION';
    case Cerrado = 'CERRADO';
    case InformeEmitido = 'INFORME_EMITIDO';
    case Cancelado = 'CANCELADO';
}
