<?php

namespace App\Models;

use App\Enums\UmbralOrigen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('proceso_prueba')]
#[Fillable([
    'proceso_etapa_id', 'prueba_id', 'orden', 'peso', 'obligatoria', 'puntaje_minimo', 'umbral_origen',
    'tiempo_minimo_seg', 'tiempo_maximo_seg', 'max_intentos',
])]
class ProcesoPrueba extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'peso' => 'decimal:2',
            'obligatoria' => 'boolean',
            'puntaje_minimo' => 'decimal:2',
            'umbral_origen' => UmbralOrigen::class,
            'tiempo_minimo_seg' => 'integer',
            'tiempo_maximo_seg' => 'integer',
            'max_intentos' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProcesoEtapa, $this>
     */
    public function procesoEtapa(): BelongsTo
    {
        return $this->belongsTo(ProcesoEtapa::class);
    }

    /**
     * @return BelongsTo<Prueba, $this>
     */
    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }
}
