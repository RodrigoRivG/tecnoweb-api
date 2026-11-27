<?php

namespace App\Actions\Empresa;

use App\Models\ContactoEmpresa;
use App\Models\Proceso;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class CrearSolicitudEmpresa
{
    public function __construct(private RegistrarSolicitudEmpresa $registrar) {}

    public function handle(Usuario $usuario): Proceso
    {
        $contacto = ContactoEmpresa::query()
            ->with('empresa')
            ->where('activo', true)
            ->whereHas('persona', fn ($persona) => $persona->where('usuario_id', $usuario->id))
            ->first();

        if ($contacto === null) {
            throw ValidationException::withMessages([
                'usuario' => 'No encontramos una empresa vinculada a tu cuenta.',
            ]);
        }

        return $this->registrar->crearProcesoParaContacto($contacto, $usuario);
    }
}