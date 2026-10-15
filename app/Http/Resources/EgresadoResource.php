<?php

namespace App\Http\Resources;

use App\Models\Egresado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Egresado
 */
class EgresadoResource extends JsonResource
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
            'carrera' => ['id' => $this->carrera->id, 'nombre' => $this->carrera->nombre],
            'facultad' => ['id' => $this->carrera->facultad->id, 'nombre' => $this->carrera->facultad->nombre],
            'area_preferencia' => $this->areaPreferencia === null ? null : [
                'id' => $this->areaPreferencia->id,
                'nombre' => $this->areaPreferencia->nombre,
            ],
            'registro_universitario' => $this->registro_universitario,
            'anio_egreso' => $this->anio_egreso,
            'ppa' => $this->ppa,
        ];
    }
}
