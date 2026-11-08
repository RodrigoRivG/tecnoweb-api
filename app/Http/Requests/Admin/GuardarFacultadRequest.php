<?php

namespace App\Http\Requests\Admin;

use App\Models\Facultad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuardarFacultadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string|object>>
     */
    public function rules(): array
    {
        $facultadId = $this->route('facultad');

        return [
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Facultad::class, 'nombre')->ignore($facultadId),
            ],
            'sigla' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique(Facultad::class, 'sigla')->ignore($facultadId),
            ],
            'ppa_minimo' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'activa' => ['sometimes', 'boolean'],
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
            'ppa_minimo.decimal' => __('talento.registro.ppa_decimales'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $sigla = Str::upper(trim((string) $this->input('sigla')));

        $datos = [
            'nombre' => trim((string) $this->input('nombre')),
            'sigla' => $sigla === '' ? null : $sigla,
        ];

        if ($this->has('activa')) {
            $datos['activa'] = $this->boolean('activa');
        }

        $this->merge($datos);
    }
}

