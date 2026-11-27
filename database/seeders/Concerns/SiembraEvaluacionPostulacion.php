<?php

namespace Database\Seeders\Concerns;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Actions\Evaluacion\AvanzarEtapaTrasResultados;
use App\Actions\Evaluacion\CalificarEntrevista;
use App\Actions\Evaluacion\FinalizarIntentoPrueba;
use App\Actions\Evaluacion\GuardarRespuestaIntento;
use App\Actions\Evaluacion\IniciarIntentoPrueba;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\TipoPrueba;
use App\Models\AsignacionPrueba;
use App\Models\IntentoPrueba;
use App\Models\OpcionPregunta;
use App\Models\Postulacion;
use App\Models\Pregunta;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\RespuestaIntento;
use App\Models\ResultadoPrueba;
use App\Models\RubricaCriterio;
use App\Models\Usuario;
use Illuminate\Support\Collection;

trait SiembraEvaluacionPostulacion
{
    protected function limpiarIntento(int $postulacionId, int $procesoPruebaId): void
    {
        $intentoPrevio = IntentoPrueba::where('postulacion_id', $postulacionId)
            ->where('proceso_prueba_id', $procesoPruebaId)
            ->first();

        if (! $intentoPrevio) {
            return;
        }

        RespuestaIntento::where('intento_id', $intentoPrevio->id)->delete();
        ResultadoPrueba::where('intento_id', $intentoPrevio->id)->delete();
        $intentoPrevio->delete();
    }

    /**
     * @param  Collection<int, OpcionPregunta>  $opciones
     */
    protected function opcionPorCalidad(Collection $opciones, float $calidad): ?OpcionPregunta
    {
        $ordenadas = $opciones->sortByDesc('puntaje')->values();
        if ($ordenadas->isEmpty()) {
            return null;
        }

        $calidad = max(0.0, min(1.0, $calidad));
        $indice = match (true) {
            $calidad >= 0.92 => 0,
            $calidad >= 0.78 => min(1, $ordenadas->count() - 1),
            $calidad >= 0.62 => min(2, $ordenadas->count() - 1),
            default => $ordenadas->count() - 1,
        };

        return $ordenadas[$indice];
    }

    /**
     * Misma lógica que {@see AvanzarEtapaTrasResultados::reconciliarPostulacion()}.
     */
    protected function idsPruebasRequeridasEtapa(Postulacion $postulacion, ProcesoEtapa $procesoEtapa): Collection
    {
        $obligatorias = $procesoEtapa->pruebas()->with('prueba')->where('obligatoria', true)->get();
        $deFamilia = $obligatorias->filter(
            fn (ProcesoPrueba $cupo): bool => $cupo->prueba?->tipo === TipoPrueba::Test && $cupo->prueba->familia !== null,
        );
        $asignadas = AsignacionPrueba::query()
            ->where('postulacion_id', $postulacion->id)
            ->whereIn('proceso_prueba_id', $deFamilia->pluck('id'))
            ->pluck('proceso_prueba_id');

        return $obligatorias
            ->reject(fn (ProcesoPrueba $cupo): bool => $deFamilia->contains('id', $cupo->id))
            ->pluck('id')
            ->merge($asignadas)
            ->unique()
            ->values();
    }

    protected function completarEvaluacionPsicologica(
        Usuario $candidato,
        Postulacion $postulacion,
        float $calidadPsico = 1.0,
    ): void {
        $iniciar = app(IniciarIntentoPrueba::class);
        $guardar = app(GuardarRespuestaIntento::class);
        $finalizar = app(FinalizarIntentoPrueba::class);
        $asignar = app(AsignarInstrumentosPostulacion::class);

        $etapaPsico = $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->firstOrFail();

        $procesoEtapa = $etapaPsico->procesoEtapa()->with(['pruebas.prueba'])->firstOrFail();
        $asignar->handle($postulacion->refresh());

        $idsRequeridos = $this->idsPruebasRequeridasEtapa($postulacion->fresh(), $procesoEtapa);
        $pruebas = ProcesoPrueba::query()
            ->with('prueba')
            ->whereIn('id', $idsRequeridos)
            ->orderBy('orden')
            ->get();

        if ($pruebas->isEmpty()) {
            $this->command?->warn("   Sin cupos psicológicos requeridos en postulación #{$postulacion->id}; revisa AlinearBancoEvaluacionSeeder.");
        }

        $etapaPsico->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);

        foreach ($pruebas as $pp) {
            $this->limpiarIntento($postulacion->id, $pp->id);

            $postulacion->etapas()
                ->where('proceso_etapa_id', $pp->proceso_etapa_id)
                ->update([
                    'estado' => EstadoEtapa::Pendiente,
                    'iniciada_en' => now(),
                    'finalizada_en' => null,
                ]);

            $intento = $iniciar->handle($candidato, $postulacion, $pp);
            $intento->load('respuestas');

            $preguntas = Pregunta::query()
                ->whereIn('id', $intento->respuestas->pluck('pregunta_id'))
                ->with('opciones')
                ->orderBy('orden')
                ->get();

            if ($preguntas->isEmpty()) {
                $preguntas = Pregunta::where('prueba_id', $pp->prueba_id)->with('opciones')->orderBy('orden')->get();
            }

            foreach ($preguntas as $p) {
                $opcion = $this->opcionPorCalidad($p->opciones, $calidadPsico);
                if ($opcion) {
                    $guardar->handle($candidato, $intento, $p->id, $opcion->id);
                }
            }

            $finalizar->handle($candidato, $intento);
        }

        app(AvanzarEtapaTrasResultados::class)->reconciliarPostulacion($postulacion->fresh());
    }

    protected function psicologicaAprobada(Postulacion $postulacion): bool
    {
        $postulacion->refresh();
        if ($postulacion->estado === EstadoPostulacion::NoAprobada) {
            return false;
        }

        $etapa = $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->first();

        return $etapa?->estado === EstadoEtapa::Aprobada;
    }

    protected function completarEntrevista(
        Usuario $admin,
        Postulacion $postulacion,
        float $notaCriterio = 9.5,
        ?string $observaciones = null,
    ): void {
        $etapaEntrevista = $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', 'ENTREVISTA'))
            ->firstOrFail();

        $etapaEntrevista->update(['estado' => EstadoEtapa::Pendiente, 'iniciada_en' => now(), 'finalizada_en' => null]);

        $ppEntrevista = ProcesoPrueba::where('proceso_etapa_id', $etapaEntrevista->proceso_etapa_id)->firstOrFail();
        $criterios = RubricaCriterio::where('prueba_id', $ppEntrevista->prueba_id)->get();
        $califs = [];
        foreach ($criterios as $c) {
            $califs[] = [
                'criterio_id' => $c->id,
                'puntaje' => $notaCriterio,
                'comentario' => "Evaluación simulada en {$c->nombre}.",
            ];
        }

        app(CalificarEntrevista::class)->handle($admin, $postulacion, [
            'calificaciones' => $califs,
            'observaciones' => $observaciones ?? 'Entrevista de empresa simulada para datos de prueba.',
        ]);
    }
}
