<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarCarreraRequest;
use App\Http\Resources\CarreraResource;
use App\Models\AreaPreferencia;
use App\Models\Carrera;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CarreraController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $carreras = Carrera::query()
            ->when($request->filled('facultad_id'), fn (Builder $query) => $query->where('facultad_id', $request->integer('facultad_id')))
            ->with([
                'facultad',
                'areas' => fn ($areas) => $areas->orderBy('nombre'),
            ])
            ->orderBy('nombre')
            ->get();

        return CarreraResource::collection($carreras);
    }

    public function store(GuardarCarreraRequest $request): JsonResponse
    {
        $carrera = DB::transaction(function () use ($request): Carrera {
            $nueva = Carrera::query()->create([
                'facultad_id' => $request->integer('facultad_id'),
                'nombre' => $request->string('nombre')->toString(),
                'activa' => $request->boolean('activa', true),
            ]);

            $this->sincronizarAreas($nueva, $request->input('areas', []));

            return $nueva;
        });

        return (new CarreraResource($carrera->load(['facultad', 'areas' => fn ($areas) => $areas->orderBy('nombre')])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $carrera): CarreraResource
    {
        $registro = Carrera::query()
            ->with([
                'facultad',
                'areas' => fn ($areas) => $areas->orderBy('nombre'),
            ])
            ->findOrFail($carrera);

        return new CarreraResource($registro);
    }

    public function update(GuardarCarreraRequest $request, int $carrera): CarreraResource
    {
        $registro = DB::transaction(function () use ($request, $carrera): Carrera {
            $actual = Carrera::query()->findOrFail($carrera);

            $actual->update([
                'facultad_id' => $request->integer('facultad_id'),
                'nombre' => $request->string('nombre')->toString(),
                'activa' => $request->has('activa') ? $request->boolean('activa') : $actual->activa,
            ]);

            if ($request->has('areas')) {
                $this->sincronizarAreas($actual, $request->input('areas', []));
            }

            return $actual;
        });

        return new CarreraResource($registro->load(['facultad', 'areas' => fn ($areas) => $areas->orderBy('nombre')]));
    }

    public function destroy(int $carrera): Response
    {
        DB::transaction(function () use ($carrera): void {
            $registro = Carrera::query()->findOrFail($carrera);
            $registro->areas()->delete();
            $registro->delete();
        });

        return response()->noContent();
    }

    /**
     * Sincroniza las áreas de preferencia de una carrera conservando los IDs de las que permanecen.
     *
     * @param  list<string>  $nombres
     */
    private function sincronizarAreas(Carrera $carrera, array $nombres): void
    {
        $existentes = $carrera->areas()->get();

        foreach ($existentes as $area) {
            if (! in_array($area->nombre, $nombres, true)) {
                $area->delete();
            }
        }

        $nombresActuales = $existentes->pluck('nombre')->all();

        foreach ($nombres as $nombre) {
            if (! in_array($nombre, $nombresActuales, true)) {
                AreaPreferencia::query()->create([
                    'carrera_id' => $carrera->id,
                    'nombre' => $nombre,
                ]);
            }
        }
    }
}

