<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Empresa\AprobarSolicitudEmpresa;
use App\Actions\Empresa\PresentarSolicitudEmpresa;
use App\Enums\EstadoProceso;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudEmpresaController extends Controller
{
    public function index(PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        $procesos = Proceso::query()->where('tipo', 'EMPRESA')->orderByDesc('id')->get();

        return response()->json([
            'data' => $procesos->map(fn (Proceso $proceso) => $presentar->handle($proceso, $proceso->estado === EstadoProceso::EnCurso))->values(),
        ]);
    }

    public function show(Proceso $proceso, PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        abort_unless($proceso->tipo->value === 'EMPRESA', 404);

        return response()->json([
            'data' => $presentar->handle($proceso, $proceso->estado->value === 'EN_CURSO'),
        ]);
    }

    public function aprobar(Request $request, Proceso $proceso, AprobarSolicitudEmpresa $aprobar, PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        abort_unless($proceso->tipo->value === 'EMPRESA', 404);
        $aprobar->handle($proceso, $request->ip());

        return response()->json([
            'data' => $presentar->handle($proceso->fresh(), true),
        ]);
    }
}
