<?php

namespace App\Http\Resources;

use App\Models\DocumentoPostulacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DocumentoPostulacion
 */
class DocumentoPostulacionResource extends JsonResource
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
            'tipo_documento' => $this->tipoDocumento->codigo,
            'nombre_archivo' => $this->archivo->nombre_original,
            'mime_type' => $this->archivo->mime_type,
            'tamano_bytes' => $this->archivo->tamano_bytes,
            'estado' => $this->estado,
            'observacion' => $this->observacion,
            'subido_en' => $this->created_at,
            'url_descarga' => route('egresado.documentos.archivo', $this->id),
        ];
    }
}
