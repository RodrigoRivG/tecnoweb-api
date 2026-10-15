<?php

namespace App\Http\Resources;

use App\Actions\Egresado\PostularAConvocatoria;
use App\Models\Proceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Convocatoria abierta. Requiere Proceso::RELACIONES_UMBRAL cargadas.
 *
 * @mixin Proceso
 */
class ConvocatoriaResource extends JsonResource
{
    /**
     * PPA del egresado que consulta; null en el listado público.
     */
    private ?string $ppaEgresado = null;

    private ?int $postulacionId = null;

    /**
     * Agrega cumple_ppa, ya_postulado y postulacion_id para el egresado autenticado.
     */
    public function paraEgresado(?string $ppa, ?int $postulacionId): static
    {
        $this->ppaEgresado = $ppa;
        $this->postulacionId = $postulacionId;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $convocatoria = $this->convocatoria;
        $umbral = $this->umbralPpa();

        return [
            'proceso_id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'carrera' => ['id' => $convocatoria->carrera->id, 'nombre' => $convocatoria->carrera->nombre],
            'facultad' => ['id' => $convocatoria->carrera->facultad->id, 'nombre' => $convocatoria->carrera->facultad->nombre],
            'gestion' => $convocatoria->gestion,
            'numero' => $convocatoria->numero,
            'fecha_apertura' => $convocatoria->fecha_apertura->toDateString(),
            'fecha_cierre' => $convocatoria->fecha_cierre?->toDateString(),
            'ppa_minimo' => $umbral,
            $this->mergeWhen($this->ppaEgresado !== null, fn (): array => [
                'cumple_ppa' => PostularAConvocatoria::cumplePpa($this->ppaEgresado, $umbral),
                'ya_postulado' => $this->postulacionId !== null,
                'postulacion_id' => $this->postulacionId,
            ]),
        ];
    }
}
