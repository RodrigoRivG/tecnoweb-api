<?php

namespace App\Models;

use Database\Factories\AreaPreferenciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('area_preferencia')]
#[Fillable(['carrera_id', 'nombre'])]
class AreaPreferencia extends Model
{
    /** @use HasFactory<AreaPreferenciaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Carrera, $this>
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }
}
