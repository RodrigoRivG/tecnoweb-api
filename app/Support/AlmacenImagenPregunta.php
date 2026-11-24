<?php

namespace App\Support;

use App\Models\Archivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guarda la lámina de una pregunta. Si R2 está configurado, la sube allí; si no, queda en el disco público.
 */
final class AlmacenImagenPregunta
{
    public function guardar(UploadedFile $archivo, ?int $usuarioId): Archivo
    {
        $extension = $archivo->getClientOriginalExtension() ?: 'png';
        $nombre = 'pregunta_'.date('Ymd_His').'_'.Str::random(8).'.'.$extension;
        $clave = "pruebas/uploads/{$nombre}";
        $hash = hash_file('sha256', $archivo->getRealPath());
        $tamano = (int) $archivo->getSize();
        $mime = $archivo->getMimeType() ?: 'image/png';
        $claveGuardada = $clave;

        if ($this->r2Listo()) {
            try {
                Storage::disk('r2')->put($clave, fopen($archivo->getRealPath(), 'r'), 'public');
                $base = rtrim((string) config('filesystems.disks.r2.url'), '/');
                $claveGuardada = $base !== '' ? $base.'/'.$clave : $clave;
            } catch (Throwable) {
                $claveGuardada = $this->guardarEnPublico($archivo, $nombre, $clave);
            }
        } else {
            $claveGuardada = $this->guardarEnPublico($archivo, $nombre, $clave);
            $tamano = (int) File::size(public_path($clave));
        }

        return Archivo::query()->create([
            'nombre_original' => Str::limit($archivo->getClientOriginalName(), 250, ''),
            'clave_almacenamiento' => $claveGuardada,
            'mime_type' => $mime,
            'tamano_bytes' => max(1, $tamano),
            'hash_sha256' => $hash,
            'subido_por' => $usuarioId,
        ]);
    }

    private function guardarEnPublico(UploadedFile $archivo, string $nombre, string $clave): string
    {
        $directorio = public_path('pruebas/uploads');
        if (! File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0755, true, true);
        }

        $archivo->move($directorio, $nombre);

        return $clave;
    }

    private function r2Listo(): bool
    {
        $disco = config('filesystems.disks.r2');

        return is_array($disco)
            && ($disco['key'] ?? '') !== ''
            && ($disco['secret'] ?? '') !== ''
            && ($disco['bucket'] ?? '') !== ''
            && ($disco['endpoint'] ?? '') !== ''
            && class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class);
    }
}
