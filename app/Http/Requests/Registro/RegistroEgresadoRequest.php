<?php

namespace App\Http\Requests\Registro;

use App\Enums\DepartamentoCi;
use App\Models\AreaPreferencia;
use App\Models\Carrera;
use App\Models\Egresado;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegistroEgresadoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string|object>>
     */
    public function rules(): array
    {
        $mayoriaDeEdad = today(config('app.zona_horaria_negocio'))->subYears(18)->toDateString();

        return [
            'correo' => ['required', 'string', 'email', 'max:190', Rule::unique(Usuario::class, 'correo')],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->numbers()],
            'nombres' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['nullable', 'string', 'max:60', 'required_without:apellido_materno'],
            'apellido_materno' => ['nullable', 'string', 'max:60'],
            'ci_numero' => ['required', 'string', 'regex:/^\d{4,15}$/'],
            'ci_complemento' => ['string', 'regex:/^[A-Z0-9]{0,3}$/'],
            'ci_expedido' => ['nullable', Rule::enum(DepartamentoCi::class)],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$mayoriaDeEdad],
            'telefono' => ['nullable', 'string', 'max:20'],
            'carrera_id' => ['required', 'integer'],
            'area_preferencia_id' => ['nullable', 'integer'],
            'registro_universitario' => ['required', 'string', 'max:20', Rule::unique(Egresado::class, 'registro_universitario')],
            'anio_egreso' => ['required', 'integer', 'between:1950,'.today()->year],
            'ppa' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'acepta_tratamiento_datos' => ['accepted'],
            'acepta_publicacion_perfil' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'apellido_paterno.required_without' => __('talento.registro.apellido_requerido'),
            'fecha_nacimiento.before_or_equal' => __('talento.registro.menor_de_edad'),
            'ppa.decimal' => __('talento.registro.ppa_decimales'),
        ];
    }

    /**
     * Reglas que dependen de la BD: CI existente (R9), carrera activa y área de la misma carrera.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['ci_numero', 'ci_complemento', 'carrera_id', 'area_preferencia_id'])) {
                    return;
                }

                $persona = Persona::query()
                    ->where('ci_numero', $this->input('ci_numero'))
                    ->where('ci_complemento', $this->input('ci_complemento'))
                    ->first(['id', 'usuario_id']);

                if ($persona !== null) {
                    $validator->errors()->add('ci_numero', __($persona->usuario_id === null
                        ? 'talento.registro.ci_sin_cuenta'
                        : 'talento.registro.ci_con_cuenta'));
                }

                if (! Carrera::query()->activas()->whereKey($this->integer('carrera_id'))->exists()) {
                    $validator->errors()->add('carrera_id', __('talento.registro.carrera_inactiva'));

                    return;
                }

                if ($this->filled('area_preferencia_id') && ! AreaPreferencia::query()
                    ->whereKey($this->integer('area_preferencia_id'))
                    ->where('carrera_id', $this->integer('carrera_id'))
                    ->exists()) {
                    $validator->errors()->add('area_preferencia_id', __('talento.registro.area_otra_carrera'));
                }
            },
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'correo' => Str::lower(trim((string) $this->input('correo'))),
            'ci_numero' => trim((string) $this->input('ci_numero')),
            'ci_complemento' => Str::upper(trim((string) $this->input('ci_complemento'))),
            'acepta_publicacion_perfil' => $this->boolean('acepta_publicacion_perfil'),
        ]);
    }
}
