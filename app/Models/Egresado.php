<?php

namespace App\Models;

use Database\Factories\EgresadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('egresado')]
#[Fillable(['persona_id', 'carrera_id', 'area_preferencia_id', 'registro_universitario', 'anio_egreso', 'ppa', 'titulo_verificado'])]
class Egresado extends Model
{
    /** @use HasFactory<EgresadoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio_egreso' => 'integer',
            'ppa' => 'decimal:2',
            'titulo_verificado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    /**
     * @return BelongsTo<AreaPreferencia, $this>
     */
    public function areaPreferencia(): BelongsTo
    {
        return $this->belongsTo(AreaPreferencia::class);
    }
}
