<?php

namespace App\Models;

use App\Enums\CodigoRol;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
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

    public static function idDe(CodigoRol $codigo): int
    {
        return static::query()->where('codigo', $codigo->value)->valueOrFail('id');
    }
}
