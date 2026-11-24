<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('empresa')]
#[Fillable(['razon_social', 'nit', 'correo', 'telefono', 'direccion', 'estado'])]
class Empresa extends Model
{
    /**
     * @return HasMany<ContactoEmpresa, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(ContactoEmpresa::class);
    }
}
