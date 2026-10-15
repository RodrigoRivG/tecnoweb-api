<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('proceso_etapa')]
#[Fillable(['proceso_id', 'etapa_id', 'orden', 'es_eliminatoria', 'activa'])]
class ProcesoEtapa extends Model
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
            'es_eliminatoria' => 'boolean',
            'activa' => 'boolean',
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
     * @return BelongsTo<Etapa, $this>
     */
    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    /**
     * @return HasMany<ProcesoPrueba, $this>
     */
    public function pruebas(): HasMany
    {
        return $this->hasMany(ProcesoPrueba::class)->orderBy('orden');
    }
}
