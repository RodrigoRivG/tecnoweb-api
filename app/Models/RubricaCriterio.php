<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('rubrica_criterio')]
#[Fillable(['prueba_id', 'tipo_prueba', 'nombre', 'puntaje_maximo', 'orden'])]
class RubricaCriterio extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje_maximo' => 'decimal:2',
            'orden' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Prueba, $this>
     */
    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class, 'prueba_id');
    }

    /**
     * @return HasMany<CalificacionCriterio, $this>
     */
    public function calificaciones(): HasMany
    {
        return $this->hasMany(CalificacionCriterio::class, 'criterio_id');
    }
}

