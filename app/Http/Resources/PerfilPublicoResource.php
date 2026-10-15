<?php

namespace App\Http\Resources;

use App\Models\Vistas\PerfilPublico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PerfilPublico
 */
class PerfilPublicoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo_candidato' => $this->codigo_candidato,
            'facultad' => $this->facultad,
            'carrera' => $this->carrera,
            'gestion' => $this->gestion,
            'anio_egreso' => $this->anio_egreso,
            'posicion' => $this->posicion,
            'puntaje_total' => $this->puntaje_total,
            'es_top' => $this->es_top,
            'publicado_en' => $this->publicado_en,
        ];
    }
}
