<?php

namespace App\Actions\Empresa;

use App\Actions\Empresa\CompletarDatosSolicitudEmpresa;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\Proceso;
use Illuminate\Support\Facades\DB;

class PresentarSolicitudEmpresa
{
    public function __construct(private ConfirmarPagoEmpresa $pagos) {}
    /**
     * @return array<string, mixed>
     */
    public function handle(Proceso $proceso, bool $conAccesos): array
    {
        $proceso->loadMissing([
            'solicitud.contacto.persona.usuario',
            'solicitud.contacto.empresa',
            'solicitud.carrera',
            'solicitud.candidatos',
        ]);
        $solicitud = $proceso->solicitud;
        $contacto = $solicitud->contacto;
        $empresa = $contacto->empresa;

        $accesos = [];
        if ($conAccesos) {
            $postulaciones = Postulacion::query()
                ->with(['persona.usuario', 'etapas.procesoEtapa.etapa'])
                ->where('proceso_id', $proceso->id)
                ->orderBy('id')
                ->get();
            $claves = $postulaciones->isEmpty()
                ? collect()
                : DB::table('talento.invitacion_postulacion')
                    ->whereIn('postulacion_id', $postulaciones->pluck('id'))
                    ->pluck('clave_temporal', 'postulacion_id')
                    ->mapWithKeys(fn ($clave, $id): array => [(int) $id => $clave]);

            $accesos = $postulaciones->map(function (Postulacion $postulacion) use ($claves): array {
                $etapa = $postulacion->etapas
                    ->sortBy(fn (PostulacionEtapa $item): int => $item->procesoEtapa?->orden ?? 0)
                    ->first(fn (PostulacionEtapa $item): bool => in_array($item->estado?->value, [
                        EstadoEtapa::Pendiente->value,
                        EstadoEtapa::EnCurso->value,
                        EstadoEtapa::EnRevision->value,
                    ], true))
                    ?? $postulacion->etapas->sortByDesc(fn (PostulacionEtapa $item): int => $item->procesoEtapa?->orden ?? 0)->first();

                return [
                    'postulacion_id' => $postulacion->id,
                    'nombre' => trim(($postulacion->persona?->nombres ?? '').' '.($postulacion->persona?->apellido_paterno ?? '')),
                    'ci' => $postulacion->persona?->ci_numero,
                    'correo' => $postulacion->persona?->usuario?->correo,
                    'clave_temporal' => $claves->get($postulacion->id),
                    'estado_evaluacion' => $this->etiquetaEvaluacion($postulacion, $etapa),
                ];
            })->all();
        }

        return [
            'proceso_id' => $proceso->id,
            'codigo' => $proceso->codigo,
            'nombre' => $proceso->nombre,
            'estado' => $proceso->estado->value,
            'datos_puesto_pendientes' => CompletarDatosSolicitudEmpresa::datosPendientes($solicitud->cargo),
            'empresa' => [
                'razon_social' => $empresa->razon_social,
                'nit' => $empresa->nit,
                'correo' => $empresa->correo,
                'telefono' => $empresa->telefono,
            ],
            'contacto' => [
                'nombre' => $contacto->persona->nombre_completo,
                'correo' => $contacto->persona->usuario->correo,
            ],
            'puesto' => [
                'cargo' => $solicitud->cargo,
                'area' => $solicitud->area,
                'vacantes' => $solicitud->vacantes,
                'funciones' => $solicitud->descripcion_funciones,
                'carrera' => $solicitud->carrera?->nombre,
            ],
            'candidatos' => $solicitud->candidatos->map(fn ($candidato): array => [
                'nombres' => $candidato->nombres,
                'apellido_paterno' => $candidato->apellido_paterno,
                'apellidos' => trim($candidato->apellido_paterno.' '.$candidato->apellido_materno),
                'ci' => $candidato->ci_numero,
                'correo' => $candidato->correo,
                'fecha_nacimiento' => $candidato->fecha_nacimiento?->format('Y-m-d'),
            ])->all(),
            'accesos' => $accesos,
            'candidatos_pendientes_pago' => $this->pagos->candidatosPendientesDeActivacion($proceso),
        ];
    }

    private function etiquetaEvaluacion(Postulacion $postulacion, ?PostulacionEtapa $etapa): string
    {
        $cerrada = match ($postulacion->estado) {
            EstadoPostulacion::Aprobada => 'Evaluación aprobada',
            EstadoPostulacion::NoAprobada => 'No aprobada',
            EstadoPostulacion::Retirada => 'Retirada',
            default => null,
        };
        if ($cerrada !== null) {
            return $cerrada;
        }
        if ($etapa === null) {
            return 'Sin etapa';
        }

        $nombre = $etapa->procesoEtapa?->etapa?->nombre ?? 'Evaluación';
        $estado = match ($etapa->estado) {
            EstadoEtapa::Pendiente => 'Pendiente',
            EstadoEtapa::EnCurso => 'En curso',
            EstadoEtapa::EnRevision => 'En revisión',
            EstadoEtapa::Aprobada => 'Aprobada',
            EstadoEtapa::Reprobada => 'Reprobada',
            EstadoEtapa::Bloqueada => 'Bloqueada',
            EstadoEtapa::Omitida => 'Omitida',
            default => $etapa->estado?->value ?? '',
        };

        return $nombre.' · '.$estado;
    }
}
