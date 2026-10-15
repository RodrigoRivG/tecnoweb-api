<?php

namespace App\Models;

use App\Enums\EstadoEtapa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cada cambio de estado lo registran los triggers en historial_postulacion_etapa.
 * Escribir siempre dentro de Auditoria::comoUsuario() para que quede el usuario.
 */
#[Table('postulacion_etapa')]
#[Fillable(['postulacion_id', 'proceso_etapa_id', 'estado', 'asignado_a', 'asignado_en', 'iniciada_en', 'finalizada_en', 'motivo'])]
class PostulacionEtapa extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoEtapa::class,
            'asignado_en' => 'immutable_datetime',
            'iniciada_en' => 'immutable_datetime',
            'finalizada_en' => 'immutable_datetime',
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
     * @return BelongsTo<ProcesoEtapa, $this>
     */
    public function procesoEtapa(): BelongsTo
    {
        return $this->belongsTo(ProcesoEtapa::class);
    }

    /**
     * @return HasMany<HistorialPostulacionEtapa, $this>
     */
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialPostulacionEtapa::class);
    }
}
