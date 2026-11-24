<?php

namespace App\Models;

use App\Enums\EstadoIntento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('intento_prueba')]
#[Fillable(['postulacion_id', 'proceso_prueba_id', 'nro_intento', 'estado', 'iniciado_en', 'pregunta_actual_iniciada_en', 'finalizado_en'])]
class IntentoPrueba extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nro_intento' => 'integer',
            'estado' => EstadoIntento::class,
            'iniciado_en' => 'immutable_datetime',
            'pregunta_actual_iniciada_en' => 'immutable_datetime',
            'finalizado_en' => 'immutable_datetime',
            'duracion_seg' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Postulacion, $this>
     */
    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }

    /**
     * @return BelongsTo<ProcesoPrueba, $this>
     */
    public function procesoPrueba(): BelongsTo
    {
        return $this->belongsTo(ProcesoPrueba::class);
    }

    /**
     * @return HasMany<RespuestaIntento, $this>
     */
    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaIntento::class, 'intento_id');
    }

    /**
     * @return HasOne<ResultadoPrueba, $this>
     */
    public function resultado(): HasOne
    {
        return $this->hasOne(ResultadoPrueba::class, 'intento_id');
    }
}

