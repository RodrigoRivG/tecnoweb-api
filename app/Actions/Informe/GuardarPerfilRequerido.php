<?php

namespace App\Actions\Informe;

use App\Models\DimensionEvaluada;
use App\Models\Proceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarPerfilRequerido
{
    /**
     * @param  list<array{dimension_id: int, valor_ideal: ?int}>  $niveles
     */
    public function handle(Proceso $proceso, array $niveles): void
    {
        $ids = collect($niveles)->pluck('dimension_id')->map(fn ($id) => (int) $id)->unique()->values();
        $validos = DimensionEvaluada::query()
            ->whereIn('id', $ids)
            ->whereIn('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA'])
            ->pluck('id');

        if ($validos->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'niveles' => 'Solo se puede definir el perfil ideal sobre personalidad y habilidades blandas.',
            ]);
        }

        DB::transaction(function () use ($proceso, $niveles): void {
            $guardar = [];
            $borrar = [];

            foreach ($niveles as $nivel) {
                $dimensionId = (int) $nivel['dimension_id'];
                $valor = $nivel['valor_ideal'] ?? null;
                if ($valor === null) {
                    $borrar[] = $dimensionId;

                    continue;
                }

                $guardar[] = [
                    'proceso_id' => $proceso->id,
                    'dimension_id' => $dimensionId,
                    'valor_ideal' => (int) $valor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($borrar !== []) {
                DB::table('talento.perfil_requerido')
                    ->where('proceso_id', $proceso->id)
                    ->whereIn('dimension_id', $borrar)
                    ->delete();
            }

            if ($guardar !== []) {
                DB::table('talento.perfil_requerido')->upsert(
                    $guardar,
                    ['proceso_id', 'dimension_id'],
                    ['valor_ideal', 'updated_at']
                );
            }
        });
    }
}
