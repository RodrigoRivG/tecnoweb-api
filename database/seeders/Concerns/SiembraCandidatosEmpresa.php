<?php

namespace Database\Seeders\Concerns;

use App\Actions\Empresa\CompletarDatosSolicitudEmpresa;
use App\Actions\Empresa\ConfirmarPagoEmpresa;
use App\Actions\Empresa\RegistrarCandidatosEmpresa;
use App\Actions\Empresa\RegistrarSolicitudEmpresa;
use App\Enums\DepartamentoCi;
use App\Enums\EstadoPostulacion;
use App\Enums\EstadoProceso;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

trait SiembraCandidatosEmpresa
{
    use SiembraEvaluacionPostulacion;

    /**
     * @param  list<array<string, mixed>>  $solicitudes
     */
    protected function seedSolicitudesEmpresa(array $solicitudes): void
    {
        $admin = Usuario::where('correo', 'admin@talento.test')->firstOrFail();
        $registrar = app(RegistrarSolicitudEmpresa::class);
        $confirmarPago = app(ConfirmarPagoEmpresa::class);
        $registrarCandidatos = app(RegistrarCandidatosEmpresa::class);

        foreach ($solicitudes as $solicitud) {
            $carrera = Carrera::where('nombre', 'like', $solicitud['carrera_like'] ?? '%Ingeniería de Sistemas%')->first();
            if ($carrera === null) {
                $this->command?->warn("   Omitida solicitud {$solicitud['nit']}: carrera no encontrada.");

                continue;
            }

            $niveles = $this->nivelesPerfilIdeal($solicitud['perfil_ideal_default'] ?? 7);
            $passwordContacto = $solicitud['password_contacto'] ?? 'Empresa1234';
            $passwordExterno = $solicitud['password_externo'] ?? 'externo123';

            $candidatos = collect($solicitud['candidatos'])->map(fn (array $c): array => [
                'nombres' => $c['nombres'],
                'apellido_paterno' => $c['apellido_paterno'],
                'apellido_materno' => $c['apellido_materno'] ?? '',
                'ci_numero' => $c['ci_numero'],
                'fecha_nacimiento' => $c['fecha_nacimiento'],
                'correo' => $c['correo'],
            ])->all();

            $payload = [
                'razon_social' => $solicitud['razon_social'],
                'nit' => $solicitud['nit'],
                'correo_empresa' => $solicitud['correo_empresa'],
                'telefono' => $solicitud['telefono'] ?? '70000000',
                'nombres' => $solicitud['contacto_nombres'],
                'apellido_paterno' => $solicitud['contacto_apellido_paterno'],
                'apellido_materno' => $solicitud['contacto_apellido_materno'] ?? '',
                'ci_numero' => $solicitud['contacto_ci'],
                'ci_expedido' => $solicitud['contacto_ci_expedido'] ?? DepartamentoCi::SantaCruz,
                'fecha_nacimiento' => $solicitud['contacto_fecha_nacimiento'],
                'correo_contacto' => $solicitud['correo_contacto'],
                'password' => $passwordContacto,
                'password_confirmation' => $passwordContacto,
                'cargo_contacto' => $solicitud['cargo_contacto'] ?? 'Responsable de selección',
                'cargo' => $solicitud['cargo_puesto'],
                'area' => $solicitud['area'] ?? 'Recursos humanos',
                'vacantes' => count($candidatos),
                'descripcion_funciones' => $solicitud['descripcion_funciones'],
                'carrera_id' => $carrera->id,
                'niveles' => $niveles,
                'candidatos' => $candidatos,
            ];

            $proceso = $this->resolverProcesoEmpresa($registrar, $payload);

            if ($proceso->estado === EstadoProceso::PendientePago) {
                $confirmarPago->handle($proceso, null, null, 'SIMULADO');
                $proceso = $proceso->fresh();
            }

            $proceso->loadMissing('solicitud');
            if (CompletarDatosSolicitudEmpresa::datosPendientes($proceso->solicitud?->cargo)) {
                app(CompletarDatosSolicitudEmpresa::class)->handle($proceso, [
                    'cargo' => $payload['cargo'],
                    'area' => $payload['area'],
                    'vacantes' => $payload['vacantes'],
                    'descripcion_funciones' => $payload['descripcion_funciones'],
                    'carrera_id' => $payload['carrera_id'],
                    'niveles' => $payload['niveles'],
                ]);
                $proceso = $proceso->fresh();
            }

            if ($proceso->estado === EstadoProceso::PendienteCandidatos) {
                $registrarCandidatos->handle($proceso, $candidatos, '127.0.0.1');
                $proceso = $proceso->fresh();
            }

            $accesos = $this->accesosExistentes($proceso);
            if ($accesos === []) {
                $this->command?->warn("   Sin postulaciones activas para {$solicitud['razon_social']}.");

                continue;
            }

            $this->fijarClaveContacto($solicitud['correo_contacto'], $passwordContacto);

            $this->command?->info("Solicitud «{$solicitud['razon_social']}» (proceso #{$proceso->id}) — {$carrera->nombre}");

            foreach ($accesos as $indice => $acceso) {
                $meta = $solicitud['candidatos'][$indice] ?? [];
                $fase = (int) ($meta['meta_fase'] ?? 5);
                $calidad = (float) ($meta['calidad_psico'] ?? 0.88);
                $notaEntrevista = (float) ($meta['nota_entrevista'] ?? 9.0);

                $candidato = Usuario::where('correo', $acceso['correo'])->firstOrFail();
                $candidato->update(['password_hash' => Hash::make($passwordExterno)]);

                DB::table('talento.invitacion_postulacion')
                    ->where('postulacion_id', $acceso['postulacion_id'])
                    ->update([
                        'clave_temporal' => $passwordExterno,
                        'updated_at' => now(),
                    ]);

                $postulacion = Postulacion::findOrFail($acceso['postulacion_id']);

                if ($fase === 3) {
                    $this->command?->info("   -> {$acceso['correo']} · fase 3: tests psicométricos pendientes · clave {$passwordExterno}");

                    continue;
                }

                $this->completarEvaluacionPsicologica($candidato, $postulacion, $calidad);
                $postulacion->refresh();

                if (! $this->psicologicaAprobada($postulacion)) {
                    $this->command?->info("   -> {$acceso['correo']} · NO pasa umbrales psicométricos · postulación #{$postulacion->id}");

                    continue;
                }

                if ($fase === 4) {
                    $this->command?->info("   -> {$acceso['correo']} · fase 4: entrevista pendiente (panel evaluador) · #{$postulacion->id}");

                    continue;
                }

                if ($fase >= 5) {
                    $this->completarEntrevista($admin, $postulacion->refresh(), $notaEntrevista, $meta['observaciones_entrevista'] ?? null);
                    $postulacion->update(['estado' => EstadoPostulacion::Aprobada]);
                    $this->command?->info("   -> {$acceso['correo']} · proceso empresa cerrado · #{$postulacion->id}");
                }
            }

            $this->command?->info("   Empresa: {$solicitud['correo_contacto']} / {$passwordContacto}");
        }
    }

    /** @deprecated Usar seedSolicitudesEmpresa() con meta_fase por candidato. */
    protected function seedSolicitudesEmpresaCompletas(array $solicitudes): void
    {
        $conFase = array_map(function (array $sol) {
            $sol['candidatos'] = array_map(
                fn (array $c) => array_merge($c, ['meta_fase' => $c['meta_fase'] ?? 5]),
                $sol['candidatos'],
            );

            return $sol;
        }, $solicitudes);

        $this->seedSolicitudesEmpresa($conFase);
    }

    /**
     * @return list<array{postulacion_id: int, nombre: string, correo: string, clave_temporal: string}>
     */
    private function accesosExistentes(Proceso $proceso): array
    {
        return Postulacion::query()
            ->where('proceso_id', $proceso->id)
            ->with('persona.usuario')
            ->get()
            ->map(fn (Postulacion $p): array => [
                'postulacion_id' => $p->id,
                'nombre' => $p->persona?->nombre_completo ?? '',
                'correo' => $p->persona?->usuario?->correo ?? '',
                'clave_temporal' => '',
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolverProcesoEmpresa(RegistrarSolicitudEmpresa $registrar, array $payload): Proceso
    {
        try {
            $registrar->handle($payload, '127.0.0.1');
        } catch (ValidationException) {
            // Solicitud ya registrada: reutilizar el proceso.
        }

        return Proceso::query()
            ->where('tipo', 'EMPRESA')
            ->whereHas('solicitud.contacto.empresa', fn ($q) => $q->where('nit', $payload['nit']))
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function fijarClaveContacto(string $correo, string $password): void
    {
        Usuario::query()->where('correo', $correo)->update([
            'password_hash' => Hash::make($password),
        ]);
    }

    /**
     * @return list<array{dimension_id: int, valor_ideal: int|float}>
     */
    private function nivelesPerfilIdeal(int|float $default): array
    {
        return DimensionEvaluada::query()
            ->whereIn('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA'])
            ->orderBy('orden')
            ->pluck('id')
            ->map(fn (int $id): array => ['dimension_id' => $id, 'valor_ideal' => $default])
            ->all();
    }
}
