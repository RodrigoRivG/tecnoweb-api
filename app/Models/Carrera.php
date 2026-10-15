<?php

namespace App\Models;

use Database\Factories\CarreraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('carrera')]
#[Fillable(['facultad_id', 'nombre', 'activa'])]
class Carrera extends Model
{
    /** @use HasFactory<CarreraFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Facultad, $this>
     */
    public function facultad(): BelongsTo
    {
        return $this->belongsTo(Facultad::class);
    }

    /**
     * @return HasMany<AreaPreferencia, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(AreaPreferencia::class);
    }

    /**
     * @return HasMany<ConvocatoriaTalento, $this>
     */
    public function convocatorias(): HasMany
    {
        return $this->hasMany(ConvocatoriaTalento::class);
    }

    /**
     * Carreras activas de facultades activas.
     *
     * @param  Builder<Carrera>  $query
     */
    #[Scope]
    protected function activas(Builder $query): void
    {
        $query->where('activa', true)->whereHas('facultad', fn (Builder $facultad) => $facultad->where('activa', true));
    }
}
