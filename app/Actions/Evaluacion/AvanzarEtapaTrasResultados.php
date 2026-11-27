<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\Postulacion;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\ResultadoPrueba;
use Illuminate\Support\Collection;

/**
 * Cierra una etapa con pruebas cuando hay resultado en cada obligatoria asignada y
 * cumplen el umbral. Si alguna prueba calificable queda bajo el mínimo, se reprocha
 * la etapa y no se desbloquean CV ni entrevista.
 */
class AvanzarEtapaTrasResultados
{
    public function sincronizarPostulacion(Postulacion $postulacion): void
    {
        $this->reconciliarPostulacion($postulacion);
    }

    /**
     * Reevalúa cada etapa con pruebas (incluso si ya figuraba aprobada) según resultados y umbrales.
     */
    public function reconciliarPostulacion(Postulacion $postulacion): void
    {
        $postulacion->loadMissing(['etapas.procesoEtapa.etapa']);

        foreach ($postulacion->etapas as $etapaPostulacion) {
            $procesoEtapa = $etapaPostulacion->procesoEtapa;
            if ($procesoEtapa && $procesoEtapa->pruebas()->exists()) {
                $this->handle($postulacion, $procesoEtapa, reconciliar: true);
            }
        }
    }

    public function handle(Postulacion $postulacion, ProcesoEtapa $procesoEtapa, bool $reconciliar = false): void
    {
        $requeridas = $this->idsPruebasRequeridas($postulacion, $procesoEtapa);
        if ($requeridas->isEmpty()) {
            return;
        }

        $resultados = ResultadoPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $requeridas)
            ->get();

        if ($resultados->count() < $requeridas->count()) {
            return;
        }

        $etapaActual = $postulacion->etapas()
            ->where('proceso_etapa_id', $procesoEtapa->id)
            ->first();

        if (! $etapaActual) {
            return;
        }

        if (! $reconciliar && in_array($etapaActual->estado, [EstadoEtapa::Aprobada, EstadoEtapa::Reprobada], true)) {
            return;
        }

        $procesoEtapa->loadMissing(['pruebas.prueba', 'etapa']);
        $cupos = $procesoEtapa->pruebas->keyBy('id');
        $esPsicologica = $procesoEtapa->etapa?->codigo === 'EVALUACION_PSICOLOGICA';

        if ($this->hayPruebaEliminatoriaReprobada($resultados, $cupos, $requeridas)) {
            $etapaActual->update([
                'estado' => EstadoEtapa::Reprobada,
                'finalizada_en' => now(),
            ]);

            if ($esPsicologica) {
                $postulacion->update([
                    'estado' => EstadoPostulacion::NoAprobada,
                    'motivo_cierre' => 'No alcanzó el puntaje mínimo en una o más pruebas de la etapa psicológica.',
                ]);
                $this->revertirEtapasPosteriores($postulacion, $procesoEtapa->orden, incluirAprobadas: true);
            }

            return;
        }

        if ($etapaActual->estado !== EstadoEtapa::Aprobada) {
            $etapaActual->update([
                'estado' => EstadoEtapa::Aprobada,
                'finalizada_en' => now(),
            ]);
        }

        if ($postulacion->estado === EstadoPostulacion::NoAprobada && $esPsicologica) {
            $motivo = (string) ($postulacion->motivo_cierre ?? '');
            if (str_contains($motivo, 'psicológica')) {
                $postulacion->update([
                    'estado' => EstadoPostulacion::EnProceso,
                    'motivo_cierre' => null,
                ]);
            }
        }

        if ($postulacion->estado !== EstadoPostulacion::NoAprobada) {
            $this->desbloquearSiguienteEtapa($postulacion, $procesoEtapa->orden);
        }
    }

    /**
     * @param  Collection<int, ResultadoPrueba>  $resultados
     * @param  Collection<int, ProcesoPrueba>  $cupos
     * @param  Collection<int, int>  $requeridas
     */
    private function hayPruebaEliminatoriaReprobada(Collection $resultados, Collection $cupos, Collection $requeridas): bool
    {
        return $resultados
            ->filter(fn (ResultadoPrueba $r) => $requeridas->contains($r->proceso_prueba_id))
            ->contains(function (ResultadoPrueba $resultado) use ($cupos) {
                $cupo = $cupos->get($resultado->proceso_prueba_id);

                return $cupo && $this->debeReprobarPorResultado($resultado, $cupo);
            });
    }

    /**
     * El 16PF se exige completar pero no descarta por % bajo (es perfil, no cut score).
     */
    private function debeReprobarPorResultado(ResultadoPrueba $resultado, ProcesoPrueba $cupo): bool
    {
        if (! $cupo->obligatoria) {
            return false;
        }

        $codigo = $cupo->prueba?->codigo ?? '';
        if (in_array($codigo, ['16PF_FORMA_A', '16PF_FORMA_B'], true)) {
            return false;
        }

        return ! $resultado->aprobada;
    }

    private function idsPruebasRequeridas(Postulacion $postulacion, ProcesoEtapa $procesoEtapa): Collection
    {
        $obligatorias = $procesoEtapa->pruebas()->with('prueba')->where('obligatoria', true)->get();
        $deFamilia = $obligatorias->filter(
            fn ($cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test && $cupo->prueba->familia !== null,
        );
        $asignadas = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $deFamilia->pluck('id'))
            ->pluck('proceso_prueba_id');

        return $obligatorias
            ->reject(fn ($cupo): bool => $deFamilia->contains('id', $cupo->id))
            ->pluck('id')
            ->merge($asignadas)
            ->unique()
            ->values();
    }

    private function revertirEtapasPosteriores(Postulacion $postulacion, int $ordenActual, bool $incluirAprobadas = false): void
    {
        $postulacion->loadMissing('etapas.procesoEtapa');

        foreach ($postulacion->etapas as $etapaPostulacion) {
            $orden = $etapaPostulacion->procesoEtapa?->orden;
            if ($orden === null || $orden <= $ordenActual) {
                continue;
            }
            if (! $incluirAprobadas && $etapaPostulacion->estado === EstadoEtapa::Aprobada) {
                continue;
            }
            if ($etapaPostulacion->estado === EstadoEtapa::Bloqueada) {
                continue;
            }

            $etapaPostulacion->update([
                'estado' => EstadoEtapa::Bloqueada,
                'iniciada_en' => null,
                'finalizada_en' => null,
            ]);
        }
    }

    private function desbloquearSiguienteEtapa(Postulacion $postulacion, int $ordenActual): void
    {
        $siguienteEtapa = $postulacion->etapas()
            ->whereHas('procesoEtapa', fn ($q) => $q->where('orden', '>', $ordenActual)->where('activa', true))
            ->join('talento.proceso_etapa', 'talento.postulacion_etapa.proceso_etapa_id', '=', 'talento.proceso_etapa.id')
            ->orderBy('talento.proceso_etapa.orden')
            ->select('talento.postulacion_etapa.*')
            ->first();

        if ($siguienteEtapa && $siguienteEtapa->estado === EstadoEtapa::Bloqueada) {
            $siguienteEtapa->update([
                'estado' => EstadoEtapa::Pendiente,
                'iniciada_en' => now(),
            ]);
        }
    }
}
