<?php

namespace App\Actions\Empresa;

use App\Models\Proceso;

class AprobarSolicitudEmpresa
{
    public function __construct(private ActivarCandidatosEmpresa $activar) {}

    /**
     * @return list<array{postulacion_id: int, nombre: string, correo: string, clave_temporal: string}>
     */
    public function handle(Proceso $proceso, ?string $ip): array
    {
        return $this->activar->handle($proceso, $ip);
    }
}