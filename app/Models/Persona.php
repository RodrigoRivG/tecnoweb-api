<?php

namespace App\Models;

use App\Enums\DepartamentoCi;
use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('persona')]
#[Fillable([
    'usuario_id', 'nombres', 'apellido_paterno', 'apellido_materno', 'ci_numero', 'ci_complemento',
    'ci_expedido', 'fecha_nacimiento', 'telefono', 'correo_contacto',
])]
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ci_expedido' => DepartamentoCi::class,
            'fecha_nacimiento' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * @return HasMany<Egresado, $this>
     */
    public function egresados(): HasMany
    {
        return $this->hasMany(Egresado::class);
    }

    /**
     * @return HasMany<Consentimiento, $this>
     */
    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class);
    }

    /**
     * @return HasMany<Postulacion, $this>
     */
    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }
}
