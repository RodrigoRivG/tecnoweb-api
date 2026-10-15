<?php

namespace App\Models;

use App\Enums\EjecucionEtapa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('etapa')]
#[Fillable(['codigo', 'nombre', 'ejecucion', 'descripcion'])]
class Etapa extends Model
{
    public const INSCRIPCION = 'INSCRIPCION';

    public const REQUISITOS = 'REQUISITOS';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ejecucion' => EjecucionEtapa::class,
        ];
    }
}
