<?php

namespace App\Models;

use App\Enums\FamiliaPrueba;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('configuracion_familia')]
#[Fillable(['familia', 'tiempo_pregunta_seg'])]
class ConfiguracionFamilia extends Model
{
    protected $primaryKey = 'familia';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'familia' => FamiliaPrueba::class,
            'tiempo_pregunta_seg' => 'integer',
        ];
    }
}
