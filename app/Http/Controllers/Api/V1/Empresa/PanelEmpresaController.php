<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\PresentarSolicitudEmpresa;
use App\Actions\Informe\ArmarInformePostulacion;
use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use App\Models\Proceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelEmpresaController extends Controller
{
    public function index(Request $request, PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        $procesos = $this->procesosDe($request);

        return response()->json([
            'data' => $procesos->map(fn (Proceso $proceso) => $presentar->handle($proceso, $proceso->estado->value === 'EN_CURSO'))->values(),
        ]);
    }

    public function informe(Request $request, int $postulacionId, ArmarInformePostulacion $armar): JsonResponse
    {
        $postulacion = Postulacion::query()->with('proceso.solicitud.contacto.persona')->findOrFail($postulacionId);
        $dueno = $postulacion->proceso?->solicitud?->contacto?->persona?->usuario_id;
        abort_unless($dueno === $request->user()->id, 403);

        return response()->json(['data' => $armar->handle($postulacion)]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Proceso>
     */
    private function procesosDe(Request $request)
    {
        return Proceso::query()
            ->where('tipo', 'EMPRESA')
            ->whereHas('solicitud.contacto.persona', fn ($persona) => $persona->where('usuario_id', $request->user()->id))
            ->orderByDesc('id')
            ->get();
    }
}
