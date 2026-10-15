<?php

namespace App\Http\Resources;

use App\Models\AreaPreferencia;
use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Catálogo para la bienvenida y el formulario de registro. No expone ppa_minimo (dato administrativo).
 *
 * @mixin Facultad
 */
class FacultadPublicaResource extends JsonResource
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
            'carreras' => $this->carreras->map(fn (Carrera $carrera): array => [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'areas' => $carrera->areas->map(fn (AreaPreferencia $area): array => [
                    'id' => $area->id,
                    'nombre' => $area->nombre,
                ])->all(),
            ])->all(),
        ];
    }
}
