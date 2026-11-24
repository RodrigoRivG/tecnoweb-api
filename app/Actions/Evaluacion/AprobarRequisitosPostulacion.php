<?php

namespace App\Actions\Evaluacion;

use App\Enums\EstadoDocumento;
use App\Enums\EstadoEtapa;
use App\Models\Etapa;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Validation\ValidationException;

/**
 * Valida los documentos presentados y aprueba la etapa de Requisitos,
 * desbloqueando automáticamente la siguiente etapa del embudo (Evaluación Psicológica).
 */
class AprobarRequisitosPostulacion
{
    public function handle(Usuario $evaluador, Postulacion $postulacion): Postulacion
    {
        $etapaRequisitos = $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn ($q) => $q->where('codigo', Etapa::REQUISITOS))
            ->first();

        if ($etapaRequisitos === null) {
            throw ValidationException::withMessages([
                'postulacion' => 'La postulación no cuenta con etapa de requisitos.',
            ]);
        }

        return Auditoria::comoUsuario($evaluador->id, function () use ($postulacion, $etapaRequisitos): Postulacion {
            // 1. Validar todos los documentos vigentes subidos
            $postulacion->documentos()
                ->where('es_vigente', true)
                ->update(['estado' => EstadoDocumento::Validado]);

            // 2. Aprobar la etapa de requisitos
            $etapaRequisitos->update([
                'estado' => EstadoEtapa::Aprobada,
                'finalizada_en' => now(),
            ]);

            // 3. Desbloquear la siguiente etapa activa según el orden
            $ordenActual = $etapaRequisitos->procesoEtapa->orden;

            $siguienteEtapa = $postulacion->etapas()
                ->whereHas('procesoEtapa', fn ($q) => $q->where('orden', '>', $ordenActual)->where('activa', true))
                ->join('talento.proceso_etapa', 'talento.postulacion_etapa.proceso_etapa_id', '=', 'talento.proceso_etapa.id')
                ->orderBy('talento.proceso_etapa.orden')
                ->select('talento.postulacion_etapa.*')
                ->first();

            if ($siguienteEtapa && $siguienteEtapa->estado === EstadoEtapa::Bloqueada) {
                $siguienteEtapa->update([
                    'estado' => EstadoEtapa::Pendiente,
                    'iniciada_en' => now(),
                ]);
            }

            $postulacion->refresh();
            app(AsignarInstrumentosPostulacion::class)->handle($postulacion);

            return $postulacion;
        });
    }
}

