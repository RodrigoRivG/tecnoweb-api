<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'talento.permiso';

    protected $fillable = [
        'codigo',
        'nombre',
        'modulo',
        'descripcion',
    ];

    /**
     * @return BelongsToMany<Rol, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'talento.rol_permiso', 'permiso_id', 'rol_id')
            ->withTimestamps();
    }
}

