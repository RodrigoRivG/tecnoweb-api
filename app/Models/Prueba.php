<?php

namespace App\Models;

use App\Enums\TipoPrueba;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('prueba')]
#[Fillable(['codigo', 'nombre', 'tipo', 'descripcion', 'es_plantilla', 'activa'])]
class Prueba extends Model
{
    public const PPA = 'PPA';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPrueba::class,
            'es_plantilla' => 'boolean',
            'activa' => 'boolean',
        ];
    }
}
