<?php

namespace App\Http\Controllers\Api\V1\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrarVisitaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'ruta' => ['required', 'string', 'max:120', 'regex:~\A/(?:[a-z0-9-]+(?:/(?:[a-z0-9-]+|:id))*)?\z~'],
            'clave_visita' => ['required', 'uuid:4'],
        ]);

        DB::table('talento.evento_acceso')->insertOrIgnore([
            'usuario_id' => $request->user('sanctum')?->getAuthIdentifier(),
            'tipo' => 'VISITA',
            'ruta' => $datos['ruta'],
            'clave_visita' => $datos['clave_visita'],
        ]);

        $total = DB::table('talento.evento_acceso')
            ->where('tipo', 'VISITA')
            ->where('ruta', $datos['ruta'])
            ->count();

        return response()->json(['data' => ['total' => $total]]);
    }
}
