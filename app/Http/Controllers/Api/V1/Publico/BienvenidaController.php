<?php

namespace App\Http\Controllers\Api\V1\Publico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publico\FiltroPublicoRequest;
use App\Http\Resources\ConvocatoriaResource;
use App\Http\Resources\FacultadPublicaResource;
use App\Http\Resources\PerfilPublicoResource;
use App\Models\Facultad;
use App\Models\Proceso;
use App\Models\Vistas\PerfilPublico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Datos de la página de bienvenida: no requieren sesión.
 */
class BienvenidaController extends Controller
{
    public function facultades(): AnonymousResourceCollection
    {
        $facultades = Facultad::query()
            ->activas()
            ->with(['carreras' => fn ($carreras) => $carreras->where('activa', true)->orderBy('nombre')->with(['areas' => fn ($areas) => $areas->orderBy('nombre')])])
            ->orderBy('nombre')
            ->get();

        return FacultadPublicaResource::collection($facultades);
    }

    public function convocatorias(FiltroPublicoRequest $request): AnonymousResourceCollection
    {
        $convocatorias = Proceso::query()
            ->convocatoriasAbiertas()
            ->when($this->patronBusqueda($request), fn (Builder $query, string $patron) => $this->buscarConvocatorias($query, $patron))
            ->when($request->filled('carrera_id'), fn (Builder $query) => $query
                ->whereHas('convocatoria', fn (Builder $convocatoria) => $convocatoria->where('carrera_id', $request->integer('carrera_id'))))
            ->when($request->filled('facultad_id'), fn (Builder $query) => $query
                ->whereHas('convocatoria.carrera', fn (Builder $carrera) => $carrera->where('facultad_id', $request->integer('facultad_id'))))
            ->with(Proceso::RELACIONES_UMBRAL)
            ->orderBy('id')
            ->get();

        return ConvocatoriaResource::collection($convocatorias);
    }

    public function perfiles(FiltroPublicoRequest $request): AnonymousResourceCollection
    {
        $perfiles = PerfilPublico::query()
            ->when($this->patronBusqueda($request), fn (Builder $query, string $patron) => $query->whereLike('codigo_candidato', $patron))
            ->when($request->filled('facultad_id'), fn (Builder $query) => $query->where('facultad_id', $request->integer('facultad_id')))
            ->when($request->filled('carrera_id'), fn (Builder $query) => $query->where('carrera_id', $request->integer('carrera_id')))
            ->when($request->boolean('solo_top'), fn (Builder $query) => $query->where('es_top', true))
            ->orderBy('carrera')
            ->orderBy('posicion')
            ->orderBy('ranking_id')
            ->paginate(15)
            ->appends($request->safe()->except('page'));

        return PerfilPublicoResource::collection($perfiles);
    }

    private function buscarConvocatorias(Builder $query, string $patron): Builder
    {
        return $query->where(function (Builder $coincidencias) use ($patron): void {
            $this->coincidenciaNombre($coincidencias, $patron)
                ->orWhereLike('codigo', $patron)
                ->orWhereHas('convocatoria.carrera', fn (Builder $carrera) => $this->coincidenciaNombre($carrera, $patron))
                ->orWhereHas('convocatoria.carrera.facultad', fn (Builder $facultad) => $this->coincidenciaNombre($facultad, $patron));
        });
    }

    private function coincidenciaNombre(Builder $query, string $patron): Builder
    {
        return $query->whereRaw("translate(lower(nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') like ?", [$patron]);
    }

    private function patronBusqueda(FiltroPublicoRequest $request): ?string
    {
        $termino = trim((string) $request->validated('q'));

        if ($termino === '') {
            return null;
        }

        $normalizado = strtr(mb_strtolower($termino), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return '%'.addcslashes($normalizado, '\\%_').'%';
    }
}
