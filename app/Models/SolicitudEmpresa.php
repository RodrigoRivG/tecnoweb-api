<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'solicitud_empresa', key: 'proceso_id', incrementing: false)]
#[Fillable([
    'proceso_id', 'tipo_proceso', 'contacto_id', 'cargo', 'area', 'nivel_jerarquico',
    'vacantes', 'descripcion_funciones', 'carrera_id', 'enviada_en',
])]
class SolicitudEmpresa extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vacantes' => 'integer',
            'enviada_en' => 'immutable_datetime',
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
     * @return BelongsTo<ContactoEmpresa, $this>
     */
    public function contacto(): BelongsTo
    {
        return $this->belongsTo(ContactoEmpresa::class);
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * @return HasMany<CandidatoSolicitud, $this>
     */
    public function candidatos(): HasMany
    {
        return $this->hasMany(CandidatoSolicitud::class, 'proceso_id', 'proceso_id');
    }
}
