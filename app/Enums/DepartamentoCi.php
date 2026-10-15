<?php

namespace App\Enums;

/**
 * Departamento de expedición de la cédula de identidad.
 */
enum DepartamentoCi: string
{
    case LaPaz = 'LP';
    case Cochabamba = 'CB';
    case SantaCruz = 'SC';
    case Oruro = 'OR';
    case Potosi = 'PT';
    case Chuquisaca = 'CH';
    case Tarija = 'TJ';
    case Beni = 'BE';
    case Pando = 'PD';
}
