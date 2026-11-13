<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pregunta')]
#[Fillable(['prueba_id', 'tipo_prueba', 'dimension_id', 'orden', 'enunciado', 'imagen_archivo_id'])]
class Pregunta extends Model
{
    /**
     * @return BelongsTo<Prueba, $this>
     */
    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class, 'prueba_id');
    }

    /**
     * @return BelongsTo<DimensionEvaluada, $this>
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(DimensionEvaluada::class, 'dimension_id');
    }

    /**
     * @return BelongsTo<Archivo, $this>
     */
    public function imagen(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'imagen_archivo_id');
    }

    /**
     * @return HasMany<OpcionPregunta, $this>
     */
    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionPregunta::class, 'pregunta_id')->orderBy('etiqueta');
    }

    /**
     * @return HasMany<RespuestaIntento, $this>
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaIntento::class, 'pregunta_id');
    }
}

