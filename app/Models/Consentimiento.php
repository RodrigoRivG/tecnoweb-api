<?php

namespace App\Models;

use App\Enums\TipoConsentimiento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro histórico: solo se insertan filas; el estado vigente es la última por persona y tipo.
 */
#[Table('consentimiento')]
#[Fillable(['persona_id', 'tipo', 'version_politica', 'otorgado', 'registrado_en', 'ip_origen'])]
class Consentimiento extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoConsentimiento::class,
            'otorgado' => 'boolean',
            'registrado_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
