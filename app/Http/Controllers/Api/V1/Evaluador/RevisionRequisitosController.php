<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Actions\Evaluacion\AprobarRequisitosPostulacion;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostulacionResource;
use App\Models\Postulacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionRequisitosController extends Controller
{
    public function aprobar(Request $request, int $postulacionId, AprobarRequisitosPostulacion $aprobar): JsonResponse
    {
        $postulacion = Postulacion::findOrFail($postulacionId);

        $resultado = $aprobar->handle($request->user(), $postulacion);

        return (new PostulacionResource($resultado->load(PostulacionResource::RELACIONES_DETALLE)))
            ->response();
    }
}

