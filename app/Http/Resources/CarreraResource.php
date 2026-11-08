<?php

namespace App\Http\Resources;

use App\Models\AreaPreferencia;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso administrativo de carrera con su facultad y áreas de preferencia.
 *
 * @mixin Carrera
 */
class CarreraResource extends JsonResource
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
            'facultad_id' => $this->facultad_id,
            'nombre' => $this->nombre,
            'activa' => $this->activa,
            'facultad' => [
                'id' => $this->facultad->id,
                'nombre' => $this->facultad->nombre,
                'sigla' => $this->facultad->sigla,
                'activa' => $this->facultad->activa,
            ],
            'areas' => $this->areas->map(fn (AreaPreferencia $area): array => [
                'id' => $area->id,
                'nombre' => $area->nombre,
            ])->values()->all(),
        ];
    }
}

