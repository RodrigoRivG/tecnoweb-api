<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('opcion_pregunta')]
#[Fillable(['pregunta_id', 'etiqueta', 'texto', 'imagen_archivo_id', 'puntaje'])]
class OpcionPregunta extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Pregunta, $this>
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    /**
     * @return BelongsTo<Archivo, $this>
     */
    public function imagen(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'imagen_archivo_id');
    }
}

