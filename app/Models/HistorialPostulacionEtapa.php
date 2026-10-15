<?php

namespace App\Models;

use App\Enums\EstadoEtapa;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solo lectura: las filas las escriben los triggers de postulacion_etapa.
 */
#[Table('historial_postulacion_etapa')]
class HistorialPostulacionEtapa extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_anterior' => EstadoEtapa::class,
            'estado_nuevo' => EstadoEtapa::class,
        ];
    }

    /**
     * @return BelongsTo<PostulacionEtapa, $this>
     */
    public function postulacionEtapa(): BelongsTo
    {
        return $this->belongsTo(PostulacionEtapa::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
