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
            ->when($request->filled('facultad_id'), fn (Builder $query) => $query->where('facultad_id', $request->integer('facultad_id')))
            ->when($request->filled('carrera_id'), fn (Builder $query) => $query->where('carrera_id', $request->integer('carrera_id')))
            ->when($request->boolean('solo_top'), fn (Builder $query) => $query->where('es_top', true))
            ->orderBy('carrera')
            ->orderBy('posicion')
            ->paginate(15);

        return PerfilPublicoResource::collection($perfiles);
    }
}
