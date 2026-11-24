<?php

namespace App\Actions\Empresa;

use App\Models\Proceso;
use Illuminate\Support\Facades\DB;

class PresentarSolicitudEmpresa
{
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
            $filas = DB::table('talento.invitacion_postulacion as invitacion')
                ->join('talento.postulacion as postulacion', 'postulacion.id', '=', 'invitacion.postulacion_id')
                ->join('talento.persona as persona', 'persona.id', '=', 'postulacion.persona_id')
                ->join('talento.usuario as usuario', 'usuario.id', '=', 'persona.usuario_id')
                ->where('postulacion.proceso_id', $proceso->id)
                ->orderBy('postulacion.id')
                ->get([
                    'postulacion.id as postulacion_id',
                    'persona.nombres',
                    'persona.apellido_paterno',
                    'usuario.correo',
                    'invitacion.clave_temporal',
                ]);
            $accesos = $filas->map(fn ($fila): array => [
                'postulacion_id' => $fila->postulacion_id,
                'nombre' => trim($fila->nombres.' '.$fila->apellido_paterno),
                'correo' => $fila->correo,
                'clave_temporal' => $fila->clave_temporal,
            ])->all();
        }

        return [
            'proceso_id' => $proceso->id,
            'codigo' => $proceso->codigo,
            'nombre' => $proceso->nombre,
            'estado' => $proceso->estado->value,
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
                'apellidos' => trim($candidato->apellido_paterno.' '.$candidato->apellido_materno),
                'ci' => $candidato->ci_numero,
                'correo' => $candidato->correo,
            ])->all(),
            'accesos' => $accesos,
        ];
    }
}
