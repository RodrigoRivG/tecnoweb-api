<?php

namespace App\Actions\Evaluacion;

use App\Catalogos\LibrosPrueba;
use App\Enums\EstadoEtapa;
use App\Enums\FamiliaPrueba;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\Carrera;
use App\Models\IntentoPrueba;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\ResultadoPrueba;
use Illuminate\Support\Collection;

/**
 * Asigna un solo libro por familia. Las cuatro familias rotan.
 * Habilidad específica rota entre los libros activos de la carrera.
 */
class AsignarInstrumentosPostulacion
{
    public function __construct(private CompactarCuposPorFamilia $compactar) {}

    public function handle(Postulacion $postulacion): void
    {
        $postulacion->loadMissing(['etapas', 'proceso.etapas.pruebas.prueba']);

        $etapas = $postulacion->etapas->keyBy('proceso_etapa_id');
        $cupos = $postulacion->proceso->etapas
            ->flatMap(fn ($etapa) => $etapa->pruebas)
            ->filter(fn (ProcesoPrueba $cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test)
            ->values();

        $porFamilia = $cupos
            ->filter(fn (ProcesoPrueba $cupo): bool => $cupo->prueba?->familia !== null)
            ->groupBy(fn (ProcesoPrueba $cupo): string => $cupo->prueba->familia->value);

        foreach ($porFamilia as $grupo) {
            $this->asignarFamilia($postulacion, $etapas, $grupo);
        }

        foreach ($cupos->filter(fn (ProcesoPrueba $cupo): bool => $cupo->prueba?->familia === null) as $cupo) {
            $this->asignarFijo($postulacion, $etapas, $cupo);
        }

        if ($postulacion->proceso !== null) {
            $this->compactar->handle($postulacion->proceso);
        }
    }

    /**
     * Cupos que el postulante debe ver: uno por familia, o el que ya empezó.
     *
     * @return Collection<int, ProcesoPrueba>
     */
    public function cuposVisibles(Postulacion $postulacion): Collection
    {
        $cupos = ProcesoPrueba::query()
            ->with('prueba')
            ->whereHas('procesoEtapa', fn ($consulta) => $consulta->where('proceso_id', $postulacion->proceso_id))
            ->whereHas('prueba', fn ($consulta) => $consulta->where('tipo', TipoPrueba::Test)->whereNotNull('familia'))
            ->get();

        if ($cupos->isEmpty()) {
            return $cupos;
        }

        $ids = $cupos->pluck('id');
        $asignados = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $ids)
            ->pluck('proceso_prueba_id');
        $conProgreso = $this->idsConProgreso($postulacion->id, $ids);

        return $cupos
            ->groupBy(fn (ProcesoPrueba $cupo): string => $cupo->prueba->familia->value)
            ->flatMap(function (Collection $grupo) use ($asignados, $conProgreso): Collection {
                $empezados = $grupo->filter(fn (ProcesoPrueba $cupo): bool => $conProgreso->contains($cupo->id));
                if ($empezados->isNotEmpty()) {
                    return $empezados->values();
                }

                $marcados = $grupo->filter(fn (ProcesoPrueba $cupo): bool => $asignados->contains($cupo->id));
                if ($marcados->isNotEmpty()) {
                    return collect([$this->primero($marcados)]);
                }

                return collect([$this->primero($grupo)]);
            })
            ->values();
    }

    /**
     * @param  Collection<int, PostulacionEtapa>  $etapas
     * @param  Collection<int, ProcesoPrueba>  $grupo
     */
    private function asignarFamilia(Postulacion $postulacion, Collection $etapas, Collection $grupo): void
    {
        $ids = $grupo->pluck('id');
        $conProgreso = $this->idsConProgreso($postulacion->id, $ids);

        if ($conProgreso->isNotEmpty()) {
            AsignacionPrueba::query()
                ->where('postulacion_id', $postulacion->id)
                ->whereIn('proceso_prueba_id', $ids)
                ->whereNotIn('proceso_prueba_id', $conProgreso)
                ->delete();

            return;
        }

        $canonico = $this->primero($grupo);
        $asignadas = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $ids)
            ->orderBy('id')
            ->get();

        $conservar = $asignadas->firstWhere('proceso_prueba_id', $canonico->id) ?? $asignadas->first();
        if ($conservar !== null && (int) $conservar->proceso_prueba_id !== (int) $canonico->id) {
            $conservar->update(['proceso_prueba_id' => $canonico->id]);
        }

        AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $ids)
            ->when($conservar !== null, fn ($consulta) => $consulta->where('id', '!=', $conservar->id))
            ->delete();

        if ($conservar !== null || ! $this->etapaHabilitada($etapas->get($canonico->proceso_etapa_id))) {
            return;
        }

        AsignacionPrueba::query()->create([
            'postulacion_id' => $postulacion->id,
            'proceso_prueba_id' => $canonico->id,
            'prueba_id' => $this->elegir($postulacion, $canonico),
        ]);
    }

    /**
     * @param  Collection<int, PostulacionEtapa>  $etapas
     */
    private function asignarFijo(Postulacion $postulacion, Collection $etapas, ProcesoPrueba $cupo): void
    {
        $yaExiste = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->where('proceso_prueba_id', $cupo->id)
            ->exists();

        if ($yaExiste || ! $this->etapaHabilitada($etapas->get($cupo->proceso_etapa_id))) {
            return;
        }

        AsignacionPrueba::query()->create([
            'postulacion_id' => $postulacion->id,
            'proceso_prueba_id' => $cupo->id,
            'prueba_id' => $cupo->prueba_id,
        ]);
    }

    /**
     * @param  Collection<int, int>  $cupoIds
     * @return Collection<int, int>
     */
    private function idsConProgreso(int $postulacionId, Collection $cupoIds): Collection
    {
        $intentos = IntentoPrueba::query()
            ->where('postulacion_id', $postulacionId)
            ->whereIn('proceso_prueba_id', $cupoIds)
            ->pluck('proceso_prueba_id');

        $resultados = ResultadoPrueba::query()
            ->where('postulacion_id', $postulacionId)
            ->whereIn('proceso_prueba_id', $cupoIds)
            ->pluck('proceso_prueba_id');

        return $intentos->merge($resultados)->unique()->values();
    }

    /**
     * @param  Collection<int, ProcesoPrueba>  $cupos
     */
    private function primero(Collection $cupos): ProcesoPrueba
    {
        return $cupos
            ->sortBy(fn (ProcesoPrueba $cupo): array => [$cupo->orden ?? 0, $cupo->id])
            ->first();
    }

    private function etapaHabilitada(?PostulacionEtapa $etapa): bool
    {
        return in_array($etapa?->estado, [
            EstadoEtapa::Pendiente,
            EstadoEtapa::EnCurso,
            EstadoEtapa::EnRevision,
            EstadoEtapa::Aprobada,
            EstadoEtapa::Reprobada,
        ], true);
    }

    private function elegir(Postulacion $postulacion, ProcesoPrueba $cupo): int
    {
        $familia = $cupo->prueba?->familia;
        if ($familia === null) {
            return $cupo->prueba_id;
        }

        $candidatas = $this->librosDeFamilia($postulacion, $familia);
        if ($candidatas->isEmpty()) {
            return $cupo->prueba_id;
        }

        $conteos = AsignacionPrueba::query()
            ->whereIn('prueba_id', $candidatas->pluck('id'))
            ->whereHas('postulacion', fn ($consulta) => $consulta->where('proceso_id', $postulacion->proceso_id))
            ->selectRaw('prueba_id, count(*) as total')
            ->groupBy('prueba_id')
            ->pluck('total', 'prueba_id');

        return $candidatas
            ->sortBy(fn (Prueba $prueba): array => [(int) ($conteos[$prueba->id] ?? 0), $prueba->id])
            ->first()
            ->id;
    }

    /**
     * @return Collection<int, Prueba>
     */
    private function librosDeFamilia(Postulacion $postulacion, FamiliaPrueba $familia): Collection
    {
        $base = Prueba::query()
            ->where('familia', $familia)
            ->where('activa', true)
            ->where('tipo', TipoPrueba::Test)
            ->whereHas('preguntas')
            ->orderBy('id');

        if ($familia === FamiliaPrueba::HabilidadEspecifica) {
            $carrera = $this->carrera($postulacion);
            if ($carrera !== null) {
                $vinculadas = (clone $base)
                    ->whereHas('carreras', fn ($consulta) => $consulta->whereKey($carrera->id))
                    ->get();
                if ($vinculadas->isNotEmpty()) {
                    return $vinculadas;
                }

                $porDefecto = (clone $base)
                    ->whereIn('codigo', LibrosPrueba::trioPorCarrera($carrera->nombre))
                    ->get();
                if ($porDefecto->isNotEmpty()) {
                    return $porDefecto;
                }
            }
        }

        return $base->get();
    }

    private function carrera(Postulacion $postulacion): ?Carrera
    {
        $postulacion->loadMissing(['proceso.convocatoria.carrera', 'proceso.solicitud.carrera']);

        return $postulacion->proceso?->convocatoria?->carrera
            ?? $postulacion->proceso?->solicitud?->carrera;
    }
}
