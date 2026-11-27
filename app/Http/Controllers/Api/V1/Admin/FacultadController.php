<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarFacultadRequest;
use App\Http\Resources\FacultadResource;
use App\Models\Facultad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FacultadController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $facultades = Facultad::query()
            ->withCount('carreras')
            ->orderBy('nombre')
            ->get();

        return FacultadResource::collection($facultades);
    }

    public function store(GuardarFacultadRequest $request): JsonResponse
    {
        $facultad = Facultad::query()->create([
            'nombre' => $request->string('nombre')->toString(),
            'sigla' => $request->input('sigla'),
            'ppa_minimo' => $request->input('ppa_minimo'),
            'activa' => $request->boolean('activa', true),
        ]);

        return (new FacultadResource($facultad->loadCount('carreras')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $facultad): FacultadResource
    {
        $registro = Facultad::query()
            ->withCount('carreras')
            ->findOrFail($facultad);

        return new FacultadResource($registro);
    }

    public function update(GuardarFacultadRequest $request, int $facultad): FacultadResource
    {
        $registro = Facultad::query()->findOrFail($facultad);

        $registro->update([
            'nombre' => $request->string('nombre')->toString(),
            'sigla' => $request->input('sigla'),
            'ppa_minimo' => $request->input('ppa_minimo'),
            'activa' => $request->has('activa') ? $request->boolean('activa') : $registro->activa,
        ]);

        return new FacultadResource($registro->loadCount('carreras'));
    }

    public function destroy(int $facultad): Response|JsonResponse
    {
        $registro = Facultad::query()->withCount('carreras')->findOrFail($facultad);

        if ($registro->carreras_count > 0) {
            $mensaje = __('bd.generico.en_uso');

            return response()->json([
                'message' => $mensaje,
                'errors' => ['general' => [$mensaje]],
            ], Response::HTTP_CONFLICT);
        }

        $registro->delete();

        return response()->noContent();
    }
}

