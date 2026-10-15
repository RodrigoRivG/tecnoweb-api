<?php

namespace App\Actions\Registro;

use App\Enums\CodigoRol;
use App\Enums\EstadoUsuario;
use App\Enums\TipoConsentimiento;
use App\Models\ParametroSistema;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Crea usuario, persona, perfil de egresado y consentimientos en una transacción (spec 01, sección 4.3).
 */
class RegistrarEgresado
{
    /**
     * @param  array<string, mixed>  $datos  datos validados por RegistroEgresadoRequest
     */
    public function handle(array $datos, ?string $ipOrigen): Usuario
    {
        return DB::transaction(function () use ($datos, $ipOrigen): Usuario {
            $usuario = Usuario::query()->create([
                'rol_id' => Rol::idDe(CodigoRol::Egresado),
                'correo' => $datos['correo'],
                'password_hash' => $datos['password'],
                'estado' => EstadoUsuario::Activo,
            ]);

            $persona = $usuario->persona()->create([
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'] ?? null,
                'apellido_materno' => $datos['apellido_materno'] ?? null,
                'ci_numero' => $datos['ci_numero'],
                'ci_complemento' => $datos['ci_complemento'] ?? '',
                'ci_expedido' => $datos['ci_expedido'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'telefono' => $datos['telefono'] ?? null,
                'correo_contacto' => $datos['correo'],
            ]);

            $persona->egresados()->create([
                'carrera_id' => $datos['carrera_id'],
                'area_preferencia_id' => $datos['area_preferencia_id'] ?? null,
                'registro_universitario' => $datos['registro_universitario'],
                'anio_egreso' => $datos['anio_egreso'],
                'ppa' => $datos['ppa'],
            ]);

            $consentimiento = [
                'version_politica' => ParametroSistema::valor(ParametroSistema::VERSION_POLITICA_PRIVACIDAD),
                'ip_origen' => $ipOrigen,
            ];

            // Se registra también la elección de no publicar el perfil: el historial guarda ambas decisiones
            $persona->consentimientos()->createMany([
                [...$consentimiento, 'tipo' => TipoConsentimiento::TratamientoDatos, 'otorgado' => true],
                [...$consentimiento, 'tipo' => TipoConsentimiento::PublicacionPerfil, 'otorgado' => (bool) ($datos['acepta_publicacion_perfil'] ?? false)],
            ]);

            return $usuario;
        });
    }
}
