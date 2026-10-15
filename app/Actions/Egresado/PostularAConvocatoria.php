<?php

namespace App\Actions\Egresado;

use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Models\Egresado;
use App\Models\Etapa;
use App\Models\Persona;
use App\Models\Postulacion;
use App\Models\Proceso;
use App\Models\ProcesoEtapa;
use App\Models\ProcesoPrueba;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;

/**
 * Postulación del egresado a una convocatoria del flujo A. El único filtro es el PPA (reglas R3–R5).
 */
class PostularAConvocatoria
{
    /**
     * @throws ValidationException si la convocatoria no admite la postulación o el PPA no alcanza el mínimo
     */
    public function handle(Usuario $usuario, int $procesoId): Postulacion
    {
        $persona = $usuario->persona;
        $proceso = Proceso::query()
            ->convocatoriasAbiertas()
            ->with([...Proceso::RELACIONES_UMBRAL, 'etapas.etapa'])
            ->find($procesoId);

        if ($proceso === null) {
            $this->rechazar('talento.postulacion.no_disponible');
        }

        $egresado = $persona->egresados()->where('carrera_id', $proceso->convocatoria->carrera_id)->first();

        if ($egresado === null) {
            $this->rechazar('talento.postulacion.otra_carrera');
        }

        if ($persona->postulaciones()->where('proceso_id', $proceso->id)->exists()) {
            $this->rechazar('talento.postulacion.duplicada');
        }

        $umbral = $proceso->umbralPpa();

        if (! self::cumplePpa($egresado->ppa, $umbral)) {
            $this->rechazar('talento.postulacion.ppa_insuficiente', ['ppa' => $egresado->ppa, 'minimo' => $umbral]);
        }

        return Auditoria::comoUsuario($usuario->id, fn (): Postulacion => $this->crear($persona, $egresado, $proceso, $umbral));
    }

    /**
     * Compara en centésimos para no depender de la precisión de float. El límite es inclusivo.
     */
    public static function cumplePpa(string $ppa, string $umbral): bool
    {
        return (int) round((float) $ppa * 100) >= (int) round((float) $umbral * 100);
    }

    private function crear(Persona $persona, Egresado $egresado, Proceso $proceso, string $umbral): Postulacion
    {
        $postulacion = $persona->postulaciones()->create([
            'codigo' => Postulacion::generarCodigo('T', $proceso->convocatoria->gestion),
            'proceso_id' => $proceso->id,
            'estado' => EstadoPostulacion::EnProceso,
        ]);

        $pruebaPpa = $proceso->pruebaPpa();

        if ($pruebaPpa !== null) {
            $postulacion->resultados()->create([
                'proceso_prueba_id' => $pruebaPpa->id,
                'puntaje_bruto' => $egresado->ppa,
                'puntaje_maximo' => '100',
                'umbral_aplicado' => $umbral,
                'aprobada' => true,
            ]);
        }

        $this->crearEtapas($postulacion, $proceso, $pruebaPpa);

        return $postulacion;
    }

    /**
     * INSCRIPCION y la etapa que solo contiene el PPA quedan aprobadas; la siguiente por orden, pendiente; el resto, bloqueadas.
     */
    private function crearEtapas(Postulacion $postulacion, Proceso $proceso, ?ProcesoPrueba $pruebaPpa): void
    {
        $ahora = now();
        $hayPendiente = false;

        foreach ($proceso->etapas->where('activa', true) as $procesoEtapa) {
            if ($this->quedaAprobadaAlPostular($procesoEtapa, $pruebaPpa)) {
                $estado = EstadoEtapa::Aprobada;
            } elseif (! $hayPendiente) {
                $estado = EstadoEtapa::Pendiente;
                $hayPendiente = true;
            } else {
                $estado = EstadoEtapa::Bloqueada;
            }

            $postulacion->etapas()->create([
                'proceso_etapa_id' => $procesoEtapa->id,
                'estado' => $estado,
                'iniciada_en' => $estado === EstadoEtapa::Aprobada ? $ahora : null,
                'finalizada_en' => $estado === EstadoEtapa::Aprobada ? $ahora : null,
            ]);
        }
    }

    private function quedaAprobadaAlPostular(ProcesoEtapa $procesoEtapa, ?ProcesoPrueba $pruebaPpa): bool
    {
        if ($procesoEtapa->etapa->codigo === Etapa::INSCRIPCION) {
            return true;
        }

        return $pruebaPpa !== null
            && $procesoEtapa->id === $pruebaPpa->proceso_etapa_id
            && $procesoEtapa->pruebas->count() === 1;
    }

    /**
     * @param  array<string, string>  $reemplazos
     */
    private function rechazar(string $mensaje, array $reemplazos = []): never
    {
        throw ValidationException::withMessages(['proceso_id' => __($mensaje, $reemplazos)]);
    }
}
