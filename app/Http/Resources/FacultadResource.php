<?php

namespace App\Http\Resources;

use App\Models\Facultad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso administrativo de facultad (incluye ppa_minimo, estado y conteo de carreras).
 *
 * @mixin Facultad
 */
class FacultadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'sigla' => $this->sigla,
            'ppa_minimo' => $this->ppa_minimo,
            'activa' => $this->activa,
            'carreras_count' => (int) ($this->carreras_count ?? $this->carreras()->count()),
        ];
    }
}

