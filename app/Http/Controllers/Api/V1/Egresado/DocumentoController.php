<?php

namespace App\Http\Controllers\Api\V1\Egresado;

use App\Actions\Egresado\SubirDocumentoPostulacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egresado\SubirDocumentoRequest;
use App\Http\Resources\DocumentoPostulacionResource;
use App\Models\DocumentoPostulacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function store(SubirDocumentoRequest $request, int $postulacion, SubirDocumentoPostulacion $subirDocumento): JsonResponse
    {
        $usuario = $request->user();
        $postulacion = $usuario->persona->postulaciones()->with('proceso')->findOrFail($postulacion);

        $documento = $subirDocumento->handle($usuario, $postulacion, $request->validated('tipo_documento'), $request->file('archivo'));

        return (new DocumentoPostulacionResource($documento))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Descarga de un archivo propio: se entrega por stream, nunca por URL pública.
     */
    public function archivo(Request $request, int $documento): StreamedResponse
    {
        $usuario = $request->user();
        $esEvaluador = in_array($usuario->rol?->codigo, ['ADMIN', 'AYUDANTE'], true);

        $query = DocumentoPostulacion::query()->with('archivo');

        if (! $esEvaluador) {
            $personaId = $usuario->persona?->id;
            $query->whereHas('postulacion', fn (Builder $postulacion) => $postulacion->where('persona_id', $personaId));
        }

        $documentoModelo = $query->findOrFail($documento);

        return Storage::disk(SubirDocumentoPostulacion::DISCO)
            ->download($documentoModelo->archivo->clave_almacenamiento, $documentoModelo->archivo->nombre_original);
    }
}
