<?php

namespace App\Actions\Egresado;

use App\Enums\EstadoDocumento;
use App\Enums\EstadoEtapa;
use App\Enums\EstadoPostulacion;
use App\Models\Archivo;
use App\Models\DocumentoPostulacion;
use App\Models\Etapa;
use App\Models\Postulacion;
use App\Models\PostulacionEtapa;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Sube o reemplaza un documento exigido por la convocatoria (regla R7, spec 01 sección 4.5).
 */
class SubirDocumentoPostulacion
{
    /**
     * Disco privado donde viven los archivos; nunca se exponen por URL pública.
     */
    public const DISCO = 'local';

    /**
     * @throws ValidationException
     */
    public function handle(Usuario $usuario, Postulacion $postulacion, string $codigoTipo, UploadedFile $archivo): DocumentoPostulacion
    {
        $tipo = $postulacion->proceso->tiposDocumento()->where('codigo', $codigoTipo)->first();

        if ($tipo === null) {
            throw ValidationException::withMessages(['tipo_documento' => __('talento.documento.tipo_no_requerido')]);
        }

        if ($postulacion->estado !== EstadoPostulacion::EnProceso) {
            throw ValidationException::withMessages(['archivo' => __('talento.documento.postulacion_cerrada')]);
        }

        $etapaRequisitos = $this->etapaRequisitos($postulacion);

        if (in_array($etapaRequisitos?->estado, [EstadoEtapa::Aprobada, EstadoEtapa::Reprobada], true)) {
            throw ValidationException::withMessages(['archivo' => __('talento.documento.requisitos_cerrados')]);
        }

        $clave = $archivo->storeAs("postulaciones/{$postulacion->codigo}", Str::uuid().'.'.$archivo->extension(), self::DISCO);

        try {
            return Auditoria::comoUsuario($usuario->id, function () use ($usuario, $postulacion, $tipo, $archivo, $clave, $etapaRequisitos): DocumentoPostulacion {
                $registro = Archivo::query()->create([
                    'nombre_original' => Str::limit($archivo->getClientOriginalName(), 250, ''),
                    'clave_almacenamiento' => $clave,
                    'mime_type' => $archivo->getMimeType(),
                    'tamano_bytes' => $archivo->getSize(),
                    'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
                    'subido_por' => $usuario->id,
                ]);

                // La versión anterior queda como historial
                $postulacion->documentos()
                    ->where('tipo_documento_id', $tipo->id)
                    ->where('es_vigente', true)
                    ->update(['es_vigente' => false]);

                $documento = $postulacion->documentos()->create([
                    'tipo_documento_id' => $tipo->id,
                    'archivo_id' => $registro->id,
                    'estado' => EstadoDocumento::Pendiente,
                    'es_vigente' => true,
                ]);

                $this->enviarARevisionSiEstaCompleta($postulacion, $etapaRequisitos);

                return $documento->load(['tipoDocumento', 'archivo']);
            });
        } catch (Throwable $error) {
            Storage::disk(self::DISCO)->delete($clave);

            throw $error;
        }
    }

    /**
     * Tipos de documento obligatorios que aún no tienen un documento vigente.
     *
     * @return list<int>
     */
    public static function obligatoriosFaltantes(Postulacion $postulacion): array
    {
        $obligatorios = $postulacion->proceso->tiposDocumento()->wherePivot('obligatorio', true)->pluck('tipo_documento.id');
        $vigentes = $postulacion->documentos()->where('es_vigente', true)->pluck('tipo_documento_id');

        return $obligatorios->diff($vigentes)->values()->all();
    }

    private function etapaRequisitos(Postulacion $postulacion): ?PostulacionEtapa
    {
        return $postulacion->etapas()
            ->whereHas('procesoEtapa.etapa', fn (Builder $etapa) => $etapa->where('codigo', Etapa::REQUISITOS))
            ->first();
    }

    private function enviarARevisionSiEstaCompleta(Postulacion $postulacion, ?PostulacionEtapa $etapaRequisitos): void
    {
        if ($etapaRequisitos?->estado !== EstadoEtapa::Pendiente || self::obligatoriosFaltantes($postulacion) !== []) {
            return;
        }

        $etapaRequisitos->update(['estado' => EstadoEtapa::EnRevision, 'iniciada_en' => now()]);
    }
}
