<?php

namespace App\Models\Vistas;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Vista v_perfil_publico: única fuente de la landing pública. Solo lectura.
 */
#[Table(name: 'v_perfil_publico', key: 'ranking_id', timestamps: false)]
class PerfilPublico extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gestion' => 'integer',
            'anio_egreso' => 'integer',
            'posicion' => 'integer',
            'puntaje_total' => 'decimal:2',
            'es_top' => 'boolean',
            'publicado_en' => 'immutable_datetime',
        ];
    }
}
