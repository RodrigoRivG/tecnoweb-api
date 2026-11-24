<?php

namespace App\Actions\Empresa;

use App\Actions\Evaluacion\AsignarInstrumentosPostulacion;
use App\Enums\CodigoRol;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoProceso;
use App\Enums\EstadoUsuario;
use App\Enums\TipoConsentimiento;
use App\Models\ParametroSistema;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AprobarSolicitudEmpresa
{
    public function __construct(private AsignarInstrumentosPostulacion $asignar) {}

    /**
     * @return list<array{postulacion_id: int, nombre: string, correo: string, clave_temporal: string}>
     */
    public function handle(Proceso $proceso, ?string $ip): array
    {
        $proceso->load('solicitud.candidatos', 'solicitud.contacto', 'etapas.etapa');
        if ($proceso->estado !== EstadoProceso::PendienteAprobacion || $proceso->solicitud === null) {
            throw ValidationException::withMessages([
                'proceso' => 'Esta solicitud no está pendiente de aprobación.',
            ]);
        }

        return DB::transaction(function () use ($proceso, $ip): array {
            $proceso->update(['estado' => EstadoProceso::EnCurso]);
            $accesos = [];
            $version = ParametroSistema::valor(ParametroSistema::VERSION_POLITICA_PRIVACIDAD);
            $contactoId = $proceso->solicitud->contacto_id;

            foreach ($proceso->solicitud->candidatos as $candidato) {
                $clave = Str::password(10, symbols: false);
                $usuario = Usuario::query()->create([
                    'rol_id' => Rol::idDe(CodigoRol::CandidatoExterno),
                    'correo' => $candidato->correo,
                    'password_hash' => $clave,
                    'estado' => EstadoUsuario::Activo,
                ]);
                $persona = $usuario->persona()->create([
                    'nombres' => $candidato->nombres,
                    'apellido_paterno' => $candidato->apellido_paterno,
                    'apellido_materno' => $candidato->apellido_materno,
                    'ci_numero' => $candidato->ci_numero,
                    'ci_complemento' => '',
                    'fecha_nacimiento' => $candidato->fecha_nacimiento->toDateString(),
                    'correo_contacto' => $candidato->correo,
                ]);
                $persona->consentimientos()->create([
                    'tipo' => TipoConsentimiento::TratamientoDatos,
                    'version_politica' => $version,
                    'otorgado' => true,
                    'ip_origen' => $ip,
                ]);

                $postulacion = Postulacion::query()->create([
                    'codigo' => Postulacion::generarCodigo('E', (int) now()->format('Y')),
                    'proceso_id' => $proceso->id,
                    'persona_id' => $persona->id,
                    'registrada_por' => $contactoId,
                    'estado' => EstadoPostulacion::EnProceso,
                ]);

                foreach ($proceso->etapas as $etapa) {
                    $aprobada = $etapa->etapa?->codigo === 'INSCRIPCION';
                    $postulacion->etapas()->create([
                        'proceso_etapa_id' => $etapa->id,
                        'estado' => $aprobada ? EstadoEtapa::Aprobada : EstadoEtapa::Pendiente,
                        'iniciada_en' => $aprobada ? now() : null,
                        'finalizada_en' => $aprobada ? now() : null,
                    ]);
                }

                DB::table('talento.invitacion_postulacion')->insert([
                    'postulacion_id' => $postulacion->id,
                    'token_hash' => hash('sha256', Str::random(40)),
                    'estado' => 'ENVIADA',
                    'enviada_en' => now(),
                    'expira_en' => now()->addDays(30),
                    'reenvios' => 0,
                    'clave_temporal' => $clave,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->asignar->handle($postulacion);

                $accesos[] = [
                    'postulacion_id' => $postulacion->id,
                    'nombre' => trim($candidato->nombres.' '.$candidato->apellido_paterno),
                    'correo' => $candidato->correo,
                    'clave_temporal' => $clave,
                ];
            }

            return $accesos;
        });
    }
}
