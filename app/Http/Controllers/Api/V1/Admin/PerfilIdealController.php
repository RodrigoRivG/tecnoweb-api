<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Informe\GuardarPerfilRequerido;
use App\Http\Controllers\Controller;
use App\Models\DimensionEvaluada;
use App\Models\Proceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilIdealController extends Controller
{
    /**
     * Plantilla de los dos cuadros para definir el perfil ideal de un proceso.
     */
    public function show(Proceso $proceso): JsonResponse
    {
        $ideales = DB::table('talento.perfil_requerido')
            ->where('proceso_id', $proceso->id)
            ->pluck('valor_ideal', 'dimension_id');

        $personalidad = DimensionEvaluada::query()
            ->where('tipo', 'PERSONALIDAD')
            ->whereNotNull('factor')
            ->orderBy('orden')
            ->get()
            ->map(fn (DimensionEvaluada $dimension) => [
                'dimension_id' => $dimension->id,
                'codigo' => $dimension->factor,
                'nombre' => $dimension->nombre,
                'polo_bajo' => $dimension->polo_bajo,
                'polo_alto' => $dimension->polo_alto,
                'esten_ideal' => $ideales->has($dimension->id) ? (int) $ideales[$dimension->id] : null,
            ]);

        $blandas = DimensionEvaluada::query()
            ->where('tipo', 'HABILIDAD_BLANDA')
            ->orderBy('orden')
            ->get()
            ->map(fn (DimensionEvaluada $dimension) => [
                'dimension_id' => $dimension->id,
                'codigo' => $dimension->codigo,
                'nombre' => $dimension->nombre,
                'polo_bajo' => 'Bajo',
                'polo_alto' => 'Alto',
                'valor_ideal' => $ideales->has($dimension->id) ? (int) $ideales[$dimension->id] : null,
            ]);

        return response()->json([
            'data' => [
                'proceso_id' => $proceso->id,
                'nombre' => $proceso->nombre,
                'perfil_personalidad' => $personalidad,
                'perfil_habilidades_blandas' => $blandas,
            ],
        ]);
    }

    /**
     * Guarda los estenes ideales (1–10) de personalidad y habilidades blandas.
     */
    public function update(Request $request, Proceso $proceso, GuardarPerfilRequerido $guardar): JsonResponse
    {
        $datos = $request->validate([
            'niveles' => ['required', 'array', 'min:1'],
            'niveles.*.dimension_id' => ['required', 'integer'],
            'niveles.*.valor_ideal' => ['nullable', 'integer', 'between:1,10'],
        ]);

        $guardar->handle($proceso, $datos['niveles']);

        return $this->show($proceso);
    }
}
