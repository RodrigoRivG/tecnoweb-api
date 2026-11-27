<?php

namespace App\Http\Controllers\Api\V1\Empresa;

use App\Actions\Empresa\PresentarSolicitudEmpresa;
use App\Actions\Empresa\RegistrarCandidatosEmpresa;
use App\Enums\EstadoProceso;
use App\Http\Controllers\Controller;
use App\Models\Proceso;
use App\Support\ReglasValidacionCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidatosEmpresaController extends Controller
{
    public function store(Request $request, Proceso $proceso, RegistrarCandidatosEmpresa $registrar, PresentarSolicitudEmpresa $presentar): JsonResponse
    {
        $usuarioId = $request->user()->id;
        $dueno = Proceso::query()
            ->whereKey($proceso->id)
            ->where('tipo', 'EMPRESA')
            ->whereHas('solicitud.contacto.persona', fn ($q) => $q->where('usuario_id', $usuarioId))
            ->exists();
        abort_unless($dueno, 403);

        $datos = $request->validate(
            [
                'candidatos' => ['required', 'array', 'min:1', 'max:50'],
                'candidatos.*.nombres' => ['required', 'string', 'max:100'],
                'candidatos.*.apellido_paterno' => ['required', 'string', 'max:60'],
                'candidatos.*.apellido_materno' => ['nullable', 'string', 'max:60'],
                'candidatos.*.ci_numero' => ReglasValidacionCuenta::ciNumero(),
                'candidatos.*.fecha_nacimiento' => ReglasValidacionCuenta::fechaMayoriaDeEdad(),
                'candidatos.*.correo' => ['required', 'email', 'max:190'],
                'candidatos.*.telefono' => ReglasValidacionCuenta::telefonoBolivia(),
                'ampliar' => ['sometimes', 'boolean'],
            ],
            ReglasValidacionCuenta::mensajes(),
        );

        $ampliar = (bool) ($datos['ampliar'] ?? false);
        $accesos = $registrar->handle($proceso, $datos['candidatos'], $request->ip(), $ampliar);
        $proceso = $proceso->fresh();

        return response()->json([
            'data' => [
                'accesos' => $accesos,
                'pendiente_pago' => $proceso->estado === EstadoProceso::PendientePago || $ampliar,
                'ampliacion' => $ampliar,
                'cantidad_candidatos' => count($datos['candidatos']),
                'solicitud' => $presentar->handle($proceso, true),
            ],
        ], 201);
    }
}