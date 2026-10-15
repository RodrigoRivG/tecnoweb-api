<?php

namespace App\Enums;

/**
 * Códigos del catálogo rol (cargados por CatalogoSeeder).
 */
enum CodigoRol: string
{
    case Admin = 'ADMIN';
    case Ayudante = 'AYUDANTE';
    case Egresado = 'EGRESADO';
    case CandidatoExterno = 'CANDIDATO_EXTERNO';
    case Empresa = 'EMPRESA';
}
