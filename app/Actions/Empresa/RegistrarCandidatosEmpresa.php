<?php

namespace App\Actions\Empresa;

use App\Enums\EstadoProceso;
use App\Models\CandidatoSolicitud;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarCandidatosEmpresa
{
    public function __construct(private ActivarCandidatosEmpresa $activar) {}

    /**
     * @param  list<array<string, mixed>>  $candidatos
     * @return list<array{postulacion_id: int, nombre: string, correo: string, clave_temporal: string}>
     */
    public function handle(Proceso $proceso, array $candidatos, ?string $ip, bool $ampliar = false): array
    {
        $proceso->loadMissing('solicitud.contacto.persona.usuario');

        if ($ampliar) {
            if ($proceso->estado !== EstadoProceso::EnCurso) {
                throw ValidationException::withMessages([
                    'proceso' => 'Solo puedes ampliar candidatos en procesos en curso.',
                ]);
            }
        } elseif (! in_array($proceso->estado, [EstadoProceso::PendientePago, EstadoProceso::PendienteCandidatos], true)) {
            throw ValidationException::withMessages([
                'proceso' => 'En este estado no puedes modificar la lista de candidatos.',
            ]);
        }

        if (CompletarDatosSolicitudEmpresa::datosPendientes($proceso->solicitud?->cargo)) {
            throw ValidationException::withMessages([
                'proceso' => 'Completa el puesto y el perfil ideal antes de registrar candidatos.',
            ]);
        }
        if (count($candidatos) < 1 || count($candidatos) > 50) {
            throw ValidationException::withMessages([
                'candidatos' => 'Indica entre 1 y 50 candidatos.',
            ]);
        }

        if ($ampliar) {
            $existentes = CandidatoSolicitud::query()->where('proceso_id', $proceso->id)->count();
            if ($existentes + count($candidatos) > 50) {
                throw ValidationException::withMessages([
                    'candidatos' => 'La convocatoria admite hasta 50 candidatos en total.',
                ]);
            }
        }

        $this->validarLista($candidatos, $proceso, $ampliar);

        return DB::transaction(function () use ($proceso, $candidatos, $ip, $ampliar): array {
            if (! $ampliar) {
                CandidatoSolicitud::query()->where('proceso_id', $proceso->id)->delete();
            }

            foreach ($candidatos as $candidato) {
                CandidatoSolicitud::query()->create([
                    'proceso_id' => $proceso->id,
                    'nombres' => $candidato['nombres'],
                    'apellido_paterno' => $candidato['apellido_paterno'],
                    'apellido_materno' => $candidato['apellido_materno'] ?? null,
                    'ci_numero' => $candidato['ci_numero'],
                    'fecha_nacimiento' => $candidato['fecha_nacimiento'],
                    'correo' => $candidato['correo'],
                ]);
            }

            if ($proceso->estado === EstadoProceso::PendientePago || $ampliar) {
                return [];
            }

            $proceso->load('solicitud.candidatos');

            return $this->activar->handle($proceso, $ip);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $candidatos
     */
    private function validarLista(array $candidatos, Proceso $proceso, bool $ampliar): void
    {
        $errores = [];
        $contactoCi = $proceso->solicitud?->contacto?->persona?->ci_numero;
        $contactoCorreo = $proceso->solicitud?->contacto?->persona?->usuario?->correo
            ?? Usuario::query()->find($proceso->solicitud?->contacto?->persona?->usuario_id)?->correo;

        $correos = collect($candidatos)->pluck('correo')->map(fn ($c) => mb_strtolower((string) $c));
        $cis = collect($candidatos)->pluck('ci_numero');

        if ($correos->duplicates()->isNotEmpty()) {
            $errores['candidatos'] = 'Hay correos repetidos en la lista.';
        }
        if ($cis->duplicates()->isNotEmpty()) {
            $errores['candidatos'] = 'Hay cédulas repetidas en la lista.';
        }

        if ($ampliar) {
            $listaActual = CandidatoSolicitud::query()
                ->where('proceso_id', $proceso->id)
                ->get(['correo', 'ci_numero']);

            foreach ($correos as $correo) {
                if ($listaActual->contains(fn (CandidatoSolicitud $item): bool => mb_strtolower($item->correo) === $correo)) {
                    $errores['candidatos'] = 'Un candidato de la lista ya forma parte de esta convocatoria.';
                    break;
                }
            }
            foreach ($cis as $ci) {
                if ($listaActual->contains(fn (CandidatoSolicitud $item): bool => $item->ci_numero === $ci)) {
                    $errores['candidatos'] = 'Una cédula de la lista ya forma parte de esta convocatoria.';
                    break;
                }
            }
        }

        $correosProceso = Postulacion::query()
            ->where('proceso_id', $proceso->id)
            ->whereHas('persona.usuario')
            ->with('persona.usuario')
            ->get()
            ->map(fn (Postulacion $postulacion): string => mb_strtolower((string) $postulacion->persona?->usuario?->correo));

        foreach ($correos as $correo) {
            if ($correosProceso->contains($correo)) {
                $errores['candidatos'] = 'Un candidato de la lista ya tiene acceso en esta convocatoria.';
                break;
            }
        }

        $correosNuevos = $correos->filter(fn (string $correo): bool => ! $correosProceso->contains($correo));
        if ($correosNuevos->isNotEmpty()
            && (Usuario::query()->whereIn('correo', $correosNuevos)->exists()
                || ($contactoCorreo && $correosNuevos->contains(mb_strtolower($contactoCorreo))))) {
            $errores['candidatos'] = 'Un correo de la lista ya pertenece a otra cuenta.';
        }

        $cisNuevos = $cis->filter(function (string $ci) use ($proceso): bool {
            return ! Postulacion::query()
                ->where('proceso_id', $proceso->id)
                ->whereHas('persona', fn ($persona) => $persona->where('ci_numero', $ci))
                ->exists();
        });
        if ($cisNuevos->isNotEmpty()
            && (DB::table('talento.persona')->whereIn('ci_numero', $cisNuevos)->exists()
                || ($contactoCi && $cisNuevos->contains($contactoCi)))) {
            $errores['candidatos'] = 'Una cédula de la lista ya está registrada.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }
}