<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('resultado_dimension')]
#[Fillable(['resultado_prueba_id', 'dimension_id', 'valor'])]
class ResultadoDimension extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'resultado_prueba_id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
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
     * @return BelongsTo<DimensionEvaluada, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(DimensionEvaluada::class, 'dimension_id');
    }
}

