<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('calificacion_criterio')]
#[Fillable(['resultado_prueba_id', 'criterio_id', 'puntaje', 'comentario'])]
class CalificacionCriterio extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'resultado_prueba_id';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ResultadoPrueba, $this>
     */
    public function resultadoPrueba(): BelongsTo
    {
        return $this->belongsTo(ResultadoPrueba::class, 'resultado_prueba_id');
    }

    /**
     * @return BelongsTo<RubricaCriterio, $this>
     */
    public function criterio(): BelongsTo
    {
        return $this->belongsTo(RubricaCriterio::class, 'criterio_id');
    }
}

