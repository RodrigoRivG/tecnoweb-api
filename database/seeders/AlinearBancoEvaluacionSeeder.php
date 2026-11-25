<?php

namespace Database\Seeders;

use App\Actions\Evaluacion\CompactarCuposPorFamilia;
use App\Catalogos\LibrosPrueba;
use App\Enums\FamiliaPrueba;
use App\Enums\ModoAsignacion;
use App\Enums\TipoPrueba;
use App\Enums\UmbralOrigen;
use App\Models\AsignacionPrueba;
use App\Models\Carrera;
use App\Models\ConvocatoriaTalento;
use App\Models\HistorialPostulacionEtapa;
use App\Models\IntentoPrueba;
use App\Models\PostulacionEtapa;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Prueba;
use App\Models\ResultadoPrueba;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ata cada carrera a 3 libros y deja la batería psicotécnica en 4 módulos.
 */
class AlinearBancoEvaluacionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->vincularCarreras();
            $this->alinearConvocatorias();
        });
    }

    private function vincularCarreras(): void
    {
        $libros = Prueba::query()->where('tipo', TipoPrueba::Test)->get()->keyBy('codigo');

        foreach (Carrera::query()->orderBy('id')->get() as $carrera) {
            $codigos = LibrosPrueba::trioPorCarrera($carrera->nombre);
            $ids = collect($codigos)
                ->map(fn (string $codigo) => $libros->get($codigo)?->id)
                ->filter()
                ->values()
                ->all();

            if (count($ids) !== 3) {
                continue;
            }

            $carrera->pruebas()->sync($ids);
        }
    }

    private function alinearConvocatorias(): void
    {
        $libros = Prueba::query()->where('tipo', TipoPrueba::Test)->get()->keyBy('codigo');

        ConvocatoriaTalento::query()->with('carrera')->orderBy('proceso_id')->each(function (ConvocatoriaTalento $convocatoria) use ($libros): void {
            $psicologica = ProcesoEtapa::query()
                ->where('proceso_id', $convocatoria->proceso_id)
                ->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
                ->first();

            if ($psicologica === null) {
                return;
            }

            $ancla = LibrosPrueba::trioPorCarrera($convocatoria->carrera->nombre)[0] ?? null;

            $orden = 1;
            foreach (LibrosPrueba::cuposRotativos() as $cupo) {
                $this->asegurarCupo($psicologica, $libros->get($cupo['codigo']), $orden++, $cupo['peso'], ModoAsignacion::Alterna);
            }

            $this->asegurarCupo($psicologica, $ancla === null ? null : $libros->get($ancla), $orden, 5, ModoAsignacion::Fija);

            $this->quitarCuposSobrantes($psicologica, collect(LibrosPrueba::definiciones())->pluck('codigo')->all());
            app(CompactarCuposPorFamilia::class)->handle($convocatoria->proceso);
            $this->retirarEtapaBlandas($convocatoria->proceso_id);
            $this->renumerarEtapas($convocatoria->proceso_id);
        });
    }

    private function asegurarCupo(ProcesoEtapa $etapa, ?Prueba $prueba, int $orden, float $peso, ModoAsignacion $modo): void
    {
        if ($prueba === null) {
            return;
        }

        $datos = [
            'modo_asignacion' => $modo,
            'orden' => $orden,
            'peso' => $peso,
            'obligatoria' => true,
            'umbral_origen' => UmbralOrigen::Fijo,
            'tiempo_maximo_seg' => $prueba->tiempo_limite_seg,
            'limite_preguntas' => null,
            'max_intentos' => 1,
        ];

        $cupo = ProcesoPrueba::query()
            ->where('proceso_etapa_id', $etapa->id)
            ->where('prueba_id', $prueba->id)
            ->first();

        if ($cupo === null && $prueba->familia !== null) {
            $cupo = ProcesoPrueba::query()
                ->where('proceso_etapa_id', $etapa->id)
                ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', $prueba->familia))
                ->orderBy('orden')
                ->orderBy('id')
                ->first();
        }

        if ($cupo === null) {
            $etapa->pruebas()->create(['prueba_id' => $prueba->id, ...$datos]);

            return;
        }

        $cupo->update($datos);
    }

    /**
     * @param  list<string>  $codigos
     */
    private function quitarCuposSobrantes(ProcesoEtapa $etapa, array $codigos): void
    {
        $sobran = ProcesoPrueba::query()
            ->where('proceso_etapa_id', $etapa->id)
            ->whereHas('prueba', fn ($consulta) => $consulta->where('tipo', TipoPrueba::Test)->whereNotIn('codigo', $codigos))
            ->get();

        foreach ($sobran as $cupo) {
            $this->eliminarCupoSiEstaLibre($cupo);
        }
    }

    private function retirarEtapaBlandas(int $procesoId): void
    {
        $etapa = ProcesoEtapa::query()
            ->where('proceso_id', $procesoId)
            ->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'HABILIDADES_BLANDAS'))
            ->first();

        if ($etapa === null) {
            return;
        }

        $psicologica = ProcesoEtapa::query()
            ->where('proceso_id', $procesoId)
            ->whereHas('etapa', fn ($consulta) => $consulta->where('codigo', 'EVALUACION_PSICOLOGICA'))
            ->first();

        foreach ($etapa->pruebas()->get() as $cupo) {
            if (! $this->cupoOcupado($cupo)) {
                $cupo->delete();

                continue;
            }

            if ($psicologica === null) {
                return;
            }

            ProcesoPrueba::query()
                ->where('proceso_etapa_id', $psicologica->id)
                ->where('id', '!=', $cupo->id)
                ->whereHas('prueba', fn ($consulta) => $consulta->where('familia', FamiliaPrueba::HabilidadBlanda))
                ->get()
                ->each(function (ProcesoPrueba $otro): void {
                    if (! $this->cupoOcupado($otro)) {
                        $otro->delete();
                    }
                });

            $cupo->update([
                'proceso_etapa_id' => $psicologica->id,
                'modo_asignacion' => ModoAsignacion::Alterna,
                'orden' => 3,
                'limite_preguntas' => null,
            ]);
        }

        if ($etapa->pruebas()->exists()) {
            return;
        }

        $postulaciones = PostulacionEtapa::query()->where('proceso_etapa_id', $etapa->id)->pluck('id');
        if ($postulaciones->isNotEmpty()) {
            HistorialPostulacionEtapa::query()->whereIn('postulacion_etapa_id', $postulaciones)->delete();
            PostulacionEtapa::query()->whereIn('id', $postulaciones)->delete();
        }

        $etapa->delete();
    }

    private function eliminarCupoSiEstaLibre(ProcesoPrueba $cupo): bool
    {
        if ($this->cupoOcupado($cupo)) {
            return false;
        }

        $cupo->delete();

        return true;
    }

    private function cupoOcupado(ProcesoPrueba $cupo): bool
    {
        return IntentoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || ResultadoPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists()
            || AsignacionPrueba::query()->where('proceso_prueba_id', $cupo->id)->exists();
    }

    private function renumerarEtapas(int $procesoId): void
    {
        DB::table('talento.proceso_etapa')->where('proceso_id', $procesoId)->update([
            'orden' => DB::raw('orden + 100'),
        ]);

        $orden = 1;
        foreach (ProcesoEtapa::query()->where('proceso_id', $procesoId)->orderBy('orden')->get() as $etapa) {
            $etapa->update(['orden' => $orden++]);
        }
    }
}
