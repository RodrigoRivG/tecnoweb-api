<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class UsuarioResource extends JsonResource
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
            'correo' => $this->correo,
            'estado' => $this->estado,
            'rol' => [
                'codigo' => $this->rol->codigo,
                'nombre' => $this->rol->nombre,
            ],
            'persona' => $this->whenLoaded('persona', fn (): ?array => $this->persona === null ? null : [
                'nombres' => $this->persona->nombres,
                'apellido_paterno' => $this->persona->apellido_paterno,
                'apellido_materno' => $this->persona->apellido_materno,
                'egresados' => $this->when(
                    $this->persona->relationLoaded('egresados'),
                    fn () => EgresadoResource::collection($this->persona->egresados),
                ),
            ]),
        ];
    }
}
