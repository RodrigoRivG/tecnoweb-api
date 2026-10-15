<?php

namespace App\Actions\Auth;

use App\Enums\EstadoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login único para todos los roles (regla R8).
 */
class IniciarSesion
{
    /**
     * @throws ValidationException credenciales inválidas (mensaje genérico)
     */
    public function handle(string $correo, string $password): Usuario
    {
        $usuario = Usuario::query()->where('correo', $correo)->first();

        if ($usuario === null || ! Hash::check($password, $usuario->password_hash)) {
            if ($usuario?->estado === EstadoUsuario::Activo) {
                $this->registrarIntentoFallido($usuario);
            }

            throw ValidationException::withMessages(['correo' => __('auth.failed')]);
        }

        abort_if($usuario->estado === EstadoUsuario::Bloqueado, Response::HTTP_LOCKED, __('auth.blocked'));
        abort_if($usuario->estado !== EstadoUsuario::Activo, Response::HTTP_FORBIDDEN, __('auth.inactive'));

        $usuario->update(['intentos_fallidos' => 0, 'ultimo_acceso' => now()]);

        return $usuario;
    }

    /**
     * Al llegar al máximo de intentos la cuenta se bloquea y se revocan sus tokens.
     */
    private function registrarIntentoFallido(Usuario $usuario): void
    {
        $usuario->increment('intentos_fallidos');

        if ($usuario->intentos_fallidos < Usuario::MAX_INTENTOS_FALLIDOS) {
            return;
        }

        $usuario->update(['estado' => EstadoUsuario::Bloqueado]);
        $usuario->tokens()->delete();

        abort(Response::HTTP_LOCKED, __('auth.blocked'));
    }
}
