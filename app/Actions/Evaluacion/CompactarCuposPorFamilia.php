<?php

namespace App\Actions\Evaluacion;

use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\Proceso;
use App\Models\ProcesoPrueba;
use App\Models\ResultadoPrueba;
use Illuminate\Support\Collection;

/**
 * Deja un solo cupo por familia dentro de cada etapa.
 * No toca un cupo que ya tenga un intento, un resultado o una asignación.
 */
class CompactarCuposPorFamilia
{
    public function handle(Proceso $proceso): void
    {
        $cupos = ProcesoPrueba::query()
            ->with('prueba')
            ->whereHas('procesoEtapa', fn ($consulta) => $consulta->where('proceso_id', $proceso->id))
            ->get()
            ->filter(fn (ProcesoPrueba $cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test && $cupo->prueba->familia !== null)
            ->groupBy(fn (ProcesoPrueba $cupo): string => $cupo->proceso_etapa_id.'|'.$cupo->prueba->familia->value);

        foreach ($cupos as $grupo) {
            /** @var Collection<int, ProcesoPrueba> $grupo */
            if ($grupo->count() < 2) {
                continue;
            }

            $sobrantes = $grupo
                ->sortBy(fn (ProcesoPrueba $cupo): array => [$cupo->orden ?? 0, $cupo->id])
                ->slice(1);

            foreach ($sobrantes as $cupo) {
                if (! $this->ocupado($cupo)) {
                    $cupo->delete();
                }
            }
        }
    }

    private function ocupado(ProcesoPrueba $cupo): bool
    {
        return IntentoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || ResultadoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || AsignacionPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists();
    }
}
