<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'parametro_sistema', key: 'clave', keyType: 'string', incrementing: false)]
#[Fillable(['clave', 'valor', 'descripcion', 'actualizado_por'])]
class ParametroSistema extends Model
{
    public const VERSION_POLITICA_PRIVACIDAD = 'version_politica_privacidad';

    public static function valor(string $clave): string
    {
        return static::query()->whereKey($clave)->valueOrFail('valor');
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'actualizado_por');
    }
}
