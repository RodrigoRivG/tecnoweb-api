<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('contacto_empresa')]
#[Fillable(['empresa_id', 'persona_id', 'cargo', 'es_principal', 'activo'])]
class ContactoEmpresa extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
