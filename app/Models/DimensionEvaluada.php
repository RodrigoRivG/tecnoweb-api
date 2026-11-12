<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('dimension_evaluada')]
#[Fillable(['codigo', 'nombre', 'tipo', 'orden'])]
class DimensionEvaluada extends Model
{
    /**
     * @return HasMany<Pregunta, $this>
     */
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'dimension_id');
    }
}

