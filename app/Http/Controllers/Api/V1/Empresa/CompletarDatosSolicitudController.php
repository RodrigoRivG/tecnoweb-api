<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\CompletarDatosSolicitudEmpresa;
use App\Actions\Empresa\PresentarSolicitudEmpresa;
use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Proceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompletarDatosSolicitudController extends Controller
{
    public function store(Request $request, Proceso $proceso, CompletarDatosSolicitudEmpresa $completar, PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $dueno = Proceso::query()
            ->whereKey($proceso->id)
            ->where('tipo', 'EMPRESA')
            ->whereHas('solicitud.contacto.persona', fn ($q) => $q->where('usuario_id', $usuarioId))
            ->exists();
        abort_unless($dueno, 403);

        $datos = $request->validate([
            'cargo' => ['required', 'string', 'max:150'],
            'area' => ['nullable', 'string', 'max:100'],
            'vacantes' => ['required', 'integer', 'min:1', 'max:50'],
            'descripcion_funciones' => ['required', 'string', 'max:4000'],
            'carrera_id' => ['required', 'integer', Rule::exists(Carrera::class, 'id')],
            'niveles' => ['required', 'array', 'min:1'],
            'niveles.*.dimension_id' => ['required', 'integer'],
            'niveles.*.valor_ideal' => ['required', 'integer', 'between:1,10'],
        ]);

        $completar->handle($proceso, $datos);

        return response()->json([
            'data' => $presentar->handle($proceso->fresh(), false),
        ]);
    }
}