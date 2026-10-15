<?php

namespace App\Models;

use Database\Factories\ConvocatoriaTalentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subtipo del proceso para el flujo A. tipo_proceso lo fija la BD (discriminador).
 */
#[Table(name: 'convocatoria_talento', key: 'proceso_id', incrementing: false)]
#[Fillable(['proceso_id', 'carrera_id', 'gestion', 'numero', 'fecha_apertura', 'fecha_cierre', 'vigencia_perfil_meses'])]
class ConvocatoriaTalento extends Model
{
    /** @use HasFactory<ConvocatoriaTalentoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gestion' => 'integer',
            'numero' => 'integer',
            'fecha_apertura' => 'immutable_date',
            'fecha_cierre' => 'immutable_date',
            'vigencia_perfil_meses' => 'integer',
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
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }
}
