<?php

namespace App\Actions\Empresa;

use App\Enums\EstadoProceso;
use App\Models\Carrera;
use App\Models\DimensionEvaluada;
use App\Models\Proceso;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletarDatosSolicitudEmpresa
{
    public const CARGO_PENDIENTE = 'Por definir';

    public function __construct(private RegistrarSolicitudEmpresa $registrar) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function handle(Proceso $proceso, array $datos): Proceso
    {
        $proceso->loadMissing('solicitud');
        if ($proceso->solicitud === null) {
            throw ValidationException::withMessages([
                'proceso' => 'No encontramos los datos de la solicitud.',
            ]);
        }

        $datosPendientes = self::datosPendientes($proceso->solicitud->cargo);
        if ($proceso->estado === EstadoProceso::PendientePago) {
            if (! $datosPendientes) {
                throw ValidationException::withMessages([
                    'proceso' => 'El puesto ya está definido. Continúa con el pago.',
                ]);
            }
        } elseif ($proceso->estado !== EstadoProceso::PendienteCandidatos) {
            throw ValidationException::withMessages([
                'proceso' => 'Este proceso no admite cambios en el puesto en este momento.',
            ]);
        } elseif (! $datosPendientes) {
            throw ValidationException::withMessages([
                'proceso' => 'El puesto ya está definido.',
            ]);
        }

        $dimensiones = DimensionEvaluada::query()
            ->whereIn('tipo', ['PERSONALIDAD', 'HABILIDAD_BLANDA'])
            ->pluck('id');
        $enviados = collect($datos['niveles'])->pluck('dimension_id')->map(fn ($id) => (int) $id)->unique();
        if ($dimensiones->diff($enviados)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'niveles' => 'Indica un valor del 1 al 10 en cada dimensión.',
            ]);
        }

        $carrera = Carrera::query()->findOrFail($datos['carrera_id']);
        $carreraAnterior = $proceso->solicitud->carrera_id;
        $primeraDefinicion = self::datosPendientes($proceso->solicitud->cargo);

        $proceso->loadMissing('solicitud.contacto.empresa');

        return DB::transaction(function () use ($proceso, $datos, $carrera, $carreraAnterior, $primeraDefinicion): Proceso {
            $vacantes = (int) $datos['vacantes'];
            $razonSocial = $proceso->solicitud->contacto->empresa->razon_social;
            $proceso->update([
                'nombre' => $datos['cargo'].' · '.$razonSocial,
                'top_n' => min(50, max(1, $vacantes)),
            ]);
            $proceso->solicitud->update([
                'cargo' => $datos['cargo'],
                'area' => $datos['area'] ?? null,
                'vacantes' => $vacantes,
                'descripcion_funciones' => $datos['descripcion_funciones'],
                'carrera_id' => $carrera->id,
            ]);

            DB::table('talento.perfil_requerido')->where('proceso_id', $proceso->id)->delete();
            foreach ($datos['niveles'] as $nivel) {
                DB::table('talento.perfil_requerido')->insert([
                    'proceso_id' => $proceso->id,
                    'dimension_id' => $nivel['dimension_id'],
                    'valor_ideal' => $nivel['valor_ideal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($primeraDefinicion || (int) $carreraAnterior !== (int) $carrera->id) {
                $this->registrar->rearmarEtapas($proceso->fresh(), $carrera);
            }

            return $proceso->fresh(['solicitud']);
        });
    }

    public static function datosPendientes(?string $cargo): bool
    {
        return $cargo === null || $cargo === self::CARGO_PENDIENTE;
    }
}