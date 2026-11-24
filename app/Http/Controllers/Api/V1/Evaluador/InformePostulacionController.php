<?php

namespace App\Http\Controllers\Api\V1\Evaluador;

use App\Actions\Informe\ArmarInformePostulacion;
use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use Illuminate\Http\JsonResponse;

class InformePostulacionController extends Controller
{
    /**
     * Informe consolidado del candidato: curvas ideal/real y puntajes numéricos.
     */
    public function show(int $postulacionId, ArmarInformePostulacion $armar): JsonResponse
    {
        $postulacion = Postulacion::query()->findOrFail($postulacionId);

        return response()->json([
            'data' => $armar->handle($postulacion),
        ]);
    }
}
