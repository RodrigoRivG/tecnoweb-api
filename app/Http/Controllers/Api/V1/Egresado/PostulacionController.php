<?php

namespace App\Http\Controllers\Api\V1\Egresado;

use App\Actions\Egresado\PostularAConvocatoria;
use App\Http\Controllers\Controller;
use App\Http\Requests\Egresado\PostularRequest;
use App\Http\Resources\ConvocatoriaResource;
use App\Http\Resources\PostulacionResource;
use App\Models\Proceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PostulacionController extends Controller
{
    /**
     * Convocatorias abiertas de las carreras del egresado, con su situación frente a cada una.
     */
    public function convocatorias(Request $request): AnonymousResourceCollection
    {
        $persona = $request->user()->persona;
        $ppaPorCarrera = $persona->egresados()->pluck('ppa', 'carrera_id');
        $postulaciones = $persona->postulaciones()->pluck('id', 'proceso_id');

        $convocatorias = Proceso::query()
            ->convocatoriasAbiertas()
            ->whereHas('convocatoria', fn (Builder $convocatoria) => $convocatoria->whereIn('carrera_id', $ppaPorCarrera->keys()))
            ->with(Proceso::RELACIONES_UMBRAL)
            ->orderBy('id')
            ->get();

        return ConvocatoriaResource::collection($convocatorias->map(
            fn (Proceso $proceso): ConvocatoriaResource => (new ConvocatoriaResource($proceso))->paraEgresado(
                $ppaPorCarrera->get($proceso->convocatoria->carrera_id),
                $postulaciones->get($proceso->id),
            ),
        ));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $postulaciones = $request->user()->persona->postulaciones()
            ->with('proceso.convocatoria.carrera.facultad')
            ->latest('id')
            ->get();

        return PostulacionResource::collection($postulaciones);
    }

    public function store(PostularRequest $request, PostularAConvocatoria $postular): JsonResponse
    {
        $postulacion = $postular->handle($request->user(), $request->integer('proceso_id'));

        return (new PostulacionResource($postulacion->load(PostulacionResource::RELACIONES_DETALLE)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $postulacion): PostulacionResource
    {
        $postulacion = $request->user()->persona->postulaciones()
            ->with(PostulacionResource::RELACIONES_DETALLE)
            ->findOrFail($postulacion);

        return new PostulacionResource($postulacion);
    }
}
