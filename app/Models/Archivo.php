<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('archivo')]
#[Fillable(['nombre_original', 'clave_almacenamiento', 'mime_type', 'tamano_bytes', 'hash_sha256', 'subido_por'])]
#[Hidden(['clave_almacenamiento', 'hash_sha256'])]
class Archivo extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
