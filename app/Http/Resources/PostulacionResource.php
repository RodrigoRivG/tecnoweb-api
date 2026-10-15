<?php

namespace App\Http\Resources;

use App\Actions\Egresado\SubirDocumentoPostulacion;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Listado: solo datos generales. Detalle (con etapas y requisitos cargados): agrega etapas, requisitos y requisitos_completos.
 *
 * @mixin Postulacion
 */
class PostulacionResource extends JsonResource
{
    /**
     * Relaciones que necesita el detalle.
     *
     * @var list<string>
     */
    public const RELACIONES_DETALLE = [
        'proceso.convocatoria.carrera.facultad',
        'proceso.tiposDocumento',
        'etapas.procesoEtapa.etapa',
        'documentos.tipoDocumento',
        'documentos.archivo',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $convocatoria = $this->proceso->convocatoria;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'estado' => $this->estado,
            'creada_en' => $this->created_at,
            'convocatoria' => [
                'proceso_id' => $this->proceso->id,
                'nombre' => $this->proceso->nombre,
                'carrera' => $convocatoria->carrera->nombre,
                'facultad' => $convocatoria->carrera->facultad->nombre,
                'gestion' => $convocatoria->gestion,
            ],
            $this->mergeWhen($this->relationLoaded('etapas'), fn (): array => [
                'etapas' => $this->etapas
                    ->sortBy(fn (PostulacionEtapa $etapa): int => $etapa->procesoEtapa->orden)
                    ->map(fn (PostulacionEtapa $etapa): array => [
                        'codigo' => $etapa->procesoEtapa->etapa->codigo,
                        'nombre' => $etapa->procesoEtapa->etapa->nombre,
                        'orden' => $etapa->procesoEtapa->orden,
                        'estado' => $etapa->estado,
                    ])->values()->all(),
                'requisitos' => $this->requisitos(),
                'requisitos_completos' => SubirDocumentoPostulacion::obligatoriosFaltantes($this->resource) === [],
            ]),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requisitos(): array
    {
        $vigentes = $this->documentos->where('es_vigente', true)->keyBy('tipo_documento_id');

        return $this->proceso->tiposDocumento
            ->map(fn (TipoDocumento $tipo): array => [
                'tipo_documento' => $tipo->codigo,
                'nombre' => $tipo->nombre,
                'obligatorio' => $tipo->requisito->obligatorio,
                'documento' => $vigentes->has($tipo->id)
                    ? (new DocumentoPostulacionResource($vigentes->get($tipo->id)))->resolve()
                    : null,
            ])->values()->all();
    }
}
