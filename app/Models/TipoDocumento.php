<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tipo_documento')]
#[Fillable(['codigo', 'nombre'])]
class TipoDocumento extends Model
{
    public const FOTOGRAFIA = 'FOTOGRAFIA';
}
