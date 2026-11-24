<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('respuesta_intento')]
#[Fillable(['intento_id', 'pregunta_id', 'opcion_id', 'expirada', 'respondida_en'])]
class RespuestaIntento extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'intento_id'; // Eloquent default fallback

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expirada' => 'boolean',
            'respondida_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<IntentoPrueba, $this>
     */
    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoPrueba::class, 'intento_id');
    }

    /**
     * @return BelongsTo<Pregunta, $this>
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    /**
     * @return BelongsTo<OpcionPregunta, $this>
     */
    public function opcion(): BelongsTo
    {
        return $this->belongsTo(OpcionPregunta::class, 'opcion_id');
    }
}

