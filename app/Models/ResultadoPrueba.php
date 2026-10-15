<?php

namespace App\Models;

use App\Enums\MotivoNoAprobacion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * puntaje_normalizado lo calcula la BD (columna generada).
 */
#[Table('resultado_prueba')]
#[Fillable([
    'postulacion_id', 'proceso_prueba_id', 'intento_id', 'puntaje_bruto', 'puntaje_maximo', 'umbral_aplicado',
    'aprobada', 'motivo_no_aprobacion', 'evaluador_id', 'observaciones', 'calificado_en',
])]
class ResultadoPrueba extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puntaje_bruto' => 'decimal:2',
            'puntaje_maximo' => 'decimal:2',
            'puntaje_normalizado' => 'decimal:2',
            'umbral_aplicado' => 'decimal:2',
            'aprobada' => 'boolean',
            'motivo_no_aprobacion' => MotivoNoAprobacion::class,
            'calificado_en' => 'immutable_datetime',
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
}
