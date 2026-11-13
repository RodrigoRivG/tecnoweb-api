<?php

namespace App\Models;

use App\Enums\CodigoRol;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('rol')]
#[Fillable(['codigo', 'nombre', 'descripcion'])]
class Rol extends Model
{
    /**
     * @return HasMany<Usuario, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    /**
     * @return BelongsToMany<Permiso, $this>
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'talento.rol_permiso', 'rol_id', 'permiso_id')
            ->withTimestamps();
    }

    public function tienePermiso(string $codigo): bool
    {
        return $this->permisos->contains('codigo', $codigo);
    }

    public static function idDe(CodigoRol $codigo): int
    {
        return static::query()->where('codigo', $codigo->value)->valueOrFail('id');
    }
}
