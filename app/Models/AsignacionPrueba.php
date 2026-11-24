<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('asignacion_prueba')]
#[Fillable(['postulacion_id', 'proceso_prueba_id', 'prueba_id'])]
class AsignacionPrueba extends Model
{
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

    /**
     * @return BelongsTo<Prueba, $this>
     */
    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }
}
