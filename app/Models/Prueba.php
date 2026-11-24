<?php

namespace App\Models;

use App\Enums\FamiliaPrueba;
use App\Enums\TipoPrueba;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('prueba')]
#[Fillable(['codigo', 'nombre', 'tipo', 'familia', 'descripcion', 'es_plantilla', 'activa', 'limite_preguntas', 'tiempo_limite_seg'])]
class Prueba extends Model
{
    public const PPA = 'PPA';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoPrueba::class,
            'familia' => FamiliaPrueba::class,
            'es_plantilla' => 'boolean',
            'activa' => 'boolean',
            'limite_preguntas' => 'integer',
            'tiempo_limite_seg' => 'integer',
        ];
    }

    /**
     * @return HasMany<Pregunta, $this>
     */
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'prueba_id')->orderBy('orden');
    }

    /**
     * @return BelongsToMany<Carrera, $this>
     */
    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'prueba_carrera', 'prueba_id', 'carrera_id')->withTimestamps();
    }
}
