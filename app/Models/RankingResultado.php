<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('ranking_resultado')]
#[Fillable([
    'proceso_id', 'postulacion_id', 'posicion', 'puntaje_total', 'es_top',
    'visible', 'publicado_en', 'publicado_por',
])]
class RankingResultado extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'puntaje_total' => 'decimal:2',
            'es_top' => 'boolean',
            'visible' => 'boolean',
            'publicado_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    /**
     * @return BelongsTo<Postulacion, $this>
     */
    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'publicado_por');
    }
}

