<?php

namespace App\Models;

use App\Enums\EstadoDocumento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('documento_postulacion')]
#[Fillable(['postulacion_id', 'tipo_documento_id', 'archivo_id', 'estado', 'es_vigente', 'validado_por', 'validado_en', 'observacion'])]
class DocumentoPostulacion extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoDocumento::class,
            'es_vigente' => 'boolean',
            'validado_en' => 'immutable_datetime',
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
     * @return BelongsTo<TipoDocumento, $this>
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    /**
     * @return BelongsTo<Archivo, $this>
     */
    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class);
    }
}
