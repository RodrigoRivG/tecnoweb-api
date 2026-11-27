<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Reglas comunes de identidad y acceso (registro egresado, empresa y usuarios del panel).
 */
final class ReglasValidacionCuenta
{
    public const FECHA_NACIMIENTO_MINIMA = '1940-01-01';

    public static function limiteMayoriaDeEdad(): string
    {
        return today(config('app.zona_horaria_negocio'))->subYears(18)->toDateString();
    }

    /**
     * @return list<string|\Illuminate\Validation\Rules\Password>
     */
    public static function fechaMayoriaDeEdad(bool $required = true): array
    {
        $limite = self::limiteMayoriaDeEdad();
        $base = $required ? ['required'] : ['sometimes', 'required'];

        return [...$base, 'date', 'after:'.self::FECHA_NACIMIENTO_MINIMA, 'before_or_equal:'.$limite];
    }

    /**
     * @return list<string>
     */
    public static function ciNumero(bool $required = true): array
    {
        $base = $required ? ['required'] : ['sometimes', 'required'];

        return [...$base, 'string', 'regex:/^\d{4,15}$/'];
    }

    /**
     * @return list<string|\Illuminate\Validation\Rules\Password>
     */
    public static function passwordAlta(): array
    {
        return ['required', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->numbers()];
    }

    /**
     * @return list<string|\Illuminate\Validation\Rules\Password>
     */
    public static function passwordEdicion(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:255', Password::min(8)->letters()->numbers()];
    }

    /**
     * Alta desde el panel admin (un solo campo de contraseña en el formulario).
     *
     * @return list<string|\Illuminate\Validation\Rules\Password>
     */
    public static function passwordPanelAlta(): array
    {
        return ['required', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->numbers()];
    }

    /**
     * @return list<string>
     */
    public static function telefonoBolivia(bool $required = false): array
    {
        $patron = 'regex:/^\d{7,8}$/';

        return $required
            ? ['required', 'string', $patron]
            : ['nullable', 'string', 'max:20', $patron];
    }

    /**
     * @return list<string>
     */
    public static function nitBolivia(): array
    {
        return ['required', 'string', 'regex:/^\d{7,15}$/'];
    }

    /**
     * @return list<string>
     */
    public static function ciExpedidoBolivia(bool $required = true): array
    {
        $departamentos = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD'];

        return $required
            ? ['required', 'string', 'in:'.implode(',', $departamentos)]
            : ['nullable', 'string', 'in:'.implode(',', $departamentos)];
    }

    /**
     * @return array<string, string>
     */
    public static function mensajes(): array
    {
        return [
            'fecha_nacimiento.before_or_equal' => __('talento.registro.menor_de_edad'),
            'fecha_nacimiento.after' => __('talento.registro.fecha_nacimiento_invalida'),
            'ci_numero.regex' => __('talento.registro.ci_formato'),
            'telefono.regex' => __('talento.registro.telefono_formato'),
            'nit.regex' => __('talento.registro.nit_formato'),
            'correo_empresa.different' => __('talento.registro.correo_empresa_distinto'),
            'ci_expedido.required' => __('talento.registro.ci_expedido_requerido'),
        ];
    }
}