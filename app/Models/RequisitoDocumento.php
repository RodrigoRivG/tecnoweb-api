<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Documento que un proceso exige a sus postulantes (PK compuesta proceso_id + tipo_documento_id).
 */
#[Table('requisito_documento')]
class RequisitoDocumento extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obligatorio' => 'boolean',
        ];
    }
}
