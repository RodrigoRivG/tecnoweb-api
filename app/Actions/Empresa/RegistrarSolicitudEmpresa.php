<?php

namespace App\Actions\Empresa;

use App\Catalogos\LibrosPrueba;
use App\Enums\CodigoRol;
use App\Enums\EstadoProceso;
use App\Enums\EstadoUsuario;
use App\Enums\FamiliaPrueba;
use App\Enums\ModoAsignacion;
use App\Enums\TipoConsentimiento;
use App\Enums\TipoProceso;
use App\Enums\TipoPrueba;
use App\Enums\UmbralOrigen;
use App\Models\CandidatoSolicitud;
use App\Models\Carrera;
use App\Models\ContactoEmpresa;
use App\Models\DimensionEvaluada;
use App\Models\Empresa;
use App\Models\Etapa;
use App\Models\ParametroSistema;
use App\Models\Proceso;
use App\Models\Prueba;
use App\Models\Rol;
use App\Models\SolicitudEmpresa;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarSolicitudEmpresa
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function handle(array $datos, ?string $ip): Usuario
    {
        $this->validarUnicos($datos);
        $carrera = Carrera::query()->findOrFail($datos['carrera_id']);
        $dimensiones = DimensionEvaluada::query()
            ->whereIn('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA'])
            ->pluck('id');
        $enviados = collect($datos['niveles'])->pluck('dimension_id')->map(fn ($id) => (int) $id)->unique();
        if ($dimensiones->diff($enviados)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'niveles' => 'Indica un valor del 1 al 10 en cada factor de personalidad y cada habilidad blanda.',
            ]);
        }

        return DB::transaction(function () use ($datos, $ip, $carrera): Usuario {
            $empresa = Empresa::query()->create([
                'razon_social' => $datos['razon_social'],
                'nit' => $datos['nit'],
                'correo' => $datos['correo_empresa'],
                'telefono' => $datos['telefono'] ?? null,
                'estado' => 'ACTIVA',
            ]);

            $usuario = Usuario::query()->create([
                'rol_id' => Rol::idDe(CodigoRol::Empresa),
                'correo' => $datos['correo_contacto'],
                'password_hash' => $datos['password'],
                'estado' => EstadoUsuario::Activo,
            ]);

            $persona = $usuario->persona()->create([
                'nombres' => $datos['nombres'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'] ?? null,
                'ci_numero' => $datos['ci_numero'],
                'ci_complemento' => '',
                'ci_expedido' => $datos['ci_expedido'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'telefono' => $datos['telefono'] ?? null,
                'correo_contacto' => $datos['correo_contacto'],
            ]);

            $persona->consentimientos()->create([
                'tipo' => TipoConsentimiento::TratamientoDatos,
                'version_politica' => ParametroSistema::valor(ParametroSistema::VERSION_POLITICA_PRIVACIDAD),
                'otorgado' => true,
                'ip_origen' => $ip,
            ]);

            $contacto = ContactoEmpresa::query()->create([
                'empresa_id' => $empresa->id,
                'persona_id' => $persona->id,
                'cargo' => $datos['cargo_contacto'] ?? 'Contacto',
                'es_principal' => true,
                'activo' => true,
            ]);

            $proceso = Proceso::query()->create([
                'codigo' => $this->codigo(),
                'tipo' => TipoProceso::Empresa,
                'nombre' => $datos['cargo'].' · '.$empresa->razon_social,
                'estado' => EstadoProceso::PendienteAprobacion,
                'top_n' => min(50, max(1, (int) $datos['vacantes'])),
                'creado_por' => $usuario->id,
            ]);

            SolicitudEmpresa::query()->create([
                'proceso_id' => $proceso->id,
                'tipo_proceso' => TipoProceso::Empresa->value,
                'contacto_id' => $contacto->id,
                'cargo' => $datos['cargo'],
                'area' => $datos['area'] ?? null,
                'nivel_jerarquico' => $datos['nivel_jerarquico'] ?? 'TECNICO',
                'vacantes' => $datos['vacantes'],
                'descripcion_funciones' => $datos['descripcion_funciones'],
                'carrera_id' => $carrera->id,
                'enviada_en' => now(),
            ]);

            foreach ($datos['niveles'] as $nivel) {
                DB::table('talento.perfil_requerido')->insert([
                    'proceso_id' => $proceso->id,
                    'dimension_id' => $nivel['dimension_id'],
                    'valor_ideal' => $nivel['valor_ideal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($datos['candidatos'] as $candidato) {
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

            $this->armarEtapas($proceso, $carrera);

            return $usuario;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function validarUnicos(array $datos): void
    {
        $errores = [];
        if (Empresa::query()->where('nit', $datos['nit'])->exists()) {
            $errores['nit'] = 'Ya hay una empresa registrada con ese NIT.';
        }
        if (Usuario::query()->where('correo', $datos['correo_contacto'])->exists()) {
            $errores['correo_contacto'] = 'Ese correo de contacto ya tiene una cuenta.';
        }
        if (DB::table('talento.persona')->where('ci_numero', $datos['ci_numero'])->exists()) {
            $errores['ci_numero'] = 'Esa cédula ya está registrada.';
        }

        $correos = collect($datos['candidatos'])->pluck('correo')->map(fn ($correo) => mb_strtolower($correo));
        $cis = collect($datos['candidatos'])->pluck('ci_numero');
        if ($correos->duplicates()->isNotEmpty()) {
            $errores['candidatos'] = 'Hay correos repetidos en la lista.';
        }
        if ($cis->duplicates()->isNotEmpty()) {
            $errores['candidatos'] = 'Hay cédulas repetidas en la lista.';
        }
        if (Usuario::query()->whereIn('correo', $correos)->exists() || $correos->contains(mb_strtolower($datos['correo_contacto']))) {
            $errores['candidatos'] = 'Un correo de la lista ya pertenece a otra cuenta.';
        }
        if (DB::table('talento.persona')->whereIn('ci_numero', $cis)->exists() || $cis->contains($datos['ci_numero'])) {
            $errores['candidatos'] = 'Una cédula de la lista ya está registrada.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function codigo(): string
    {
        $base = 'EMP-'.now()->format('Y').'-';
        $numero = Proceso::query()->where('codigo', 'like', $base.'%')->count() + 1;
        do {
            $codigo = $base.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
            $numero++;
        } while (Proceso::query()->where('codigo', $codigo)->exists());

        return $codigo;
    }

    private function armarEtapas(Proceso $proceso, Carrera $carrera): void
    {
        $etapas = Etapa::query()->pluck('id', 'codigo');
        $psicologica = $proceso->etapas()->create([
            'etapa_id' => $etapas['EVALUACION_PSICOLOGICA'],
            'orden' => 2,
            'activa' => true,
        ]);
        $proceso->etapas()->create([
            'etapa_id' => $etapas['INSCRIPCION'],
            'orden' => 1,
            'activa' => true,
        ]);
        $entrevista = $proceso->etapas()->create([
            'etapa_id' => $etapas['ENTREVISTA'],
            'orden' => 3,
            'activa' => true,
        ]);

        foreach (LibrosPrueba::cuposRotativos() as $indice => $cupo) {
            $prueba = Prueba::query()->where('codigo', $cupo['codigo'])->first();
            if ($prueba === null) {
                continue;
            }
            $psicologica->pruebas()->create([
                'prueba_id' => $prueba->id,
                'modo_asignacion' => ModoAsignacion::Alterna,
                'orden' => $indice + 1,
                'peso' => $cupo['peso'],
                'umbral_origen' => UmbralOrigen::Fijo,
                'obligatoria' => true,
                'tiempo_maximo_seg' => $prueba->tiempo_limite_seg,
                'max_intentos' => 1,
            ]);
        }

        $vinculadas = $carrera->pruebas()
            ->where('tipo', TipoPrueba::Test)
            ->where('familia', FamiliaPrueba::HabilidadEspecifica)
            ->where('activa', true)
            ->orderBy('codigo')
            ->get();
        $especificas = $vinculadas->isNotEmpty()
            ? $vinculadas->values()
            : Prueba::query()->whereIn('codigo', LibrosPrueba::trioPorCarrera($carrera->nombre))->get();

        if ($vinculadas->isEmpty()) {
            foreach ($especificas as $especifica) {
                $especifica->carreras()->syncWithoutDetaching([$carrera->id]);
            }
        }

        $especifica = $especificas->sortBy('id')->first();
        if ($especifica !== null) {
            $psicologica->pruebas()->create([
                'prueba_id' => $especifica->id,
                'modo_asignacion' => ModoAsignacion::Fija,
                'orden' => 4,
                'peso' => 5,
                'umbral_origen' => UmbralOrigen::Fijo,
                'obligatoria' => true,
                'tiempo_maximo_seg' => $especifica->tiempo_limite_seg,
                'max_intentos' => 1,
            ]);
        }

        $pruebaEntrevista = Prueba::query()->where('codigo', 'ENTREVISTA')->first();
        if ($pruebaEntrevista !== null) {
            $entrevista->pruebas()->create([
                'prueba_id' => $pruebaEntrevista->id,
                'modo_asignacion' => ModoAsignacion::Fija,
                'orden' => 1,
                'peso' => 20,
                'umbral_origen' => UmbralOrigen::Fijo,
                'obligatoria' => true,
                'max_intentos' => 1,
            ]);
        }
    }
}
