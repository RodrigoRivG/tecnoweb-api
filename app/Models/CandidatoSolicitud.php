<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('candidato_solicitud')]
#[Fillable([
    'proceso_id', 'nombres', 'apellido_paterno', 'apellido_materno',
    'ci_numero', 'fecha_nacimiento', 'correo',
])]
class CandidatoSolicitud extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }
}
