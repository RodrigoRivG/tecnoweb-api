<?php

namespace App\Http\Requests\Admin;

use App\Models\Carrera;
use App\Models\Facultad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCarreraRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string|object>>
     */
    public function rules(): array
    {
        $carreraId = $this->route('carrera');

        return [
            'facultad_id' => ['required', 'integer', Rule::exists(Facultad::class, 'id')],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Carrera::class, 'nombre')
                    ->where('facultad_id', $this->input('facultad_id'))
                    ->ignore($carreraId),
            ],
            'activa' => ['sometimes', 'boolean'],
            'areas' => ['sometimes', 'array'],
            'areas.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $datos = [
            'nombre' => trim((string) $this->input('nombre')),
        ];

        if ($this->has('activa')) {
            $datos['activa'] = $this->boolean('activa');
        }

        if (is_array($this->input('areas'))) {
            $datos['areas'] = array_values(array_filter(
                array_map(static fn (mixed $area): string => trim((string) $area), $this->input('areas')),
                static fn (string $area): bool => $area !== '',
            ));
        }

        $this->merge($datos);
    }
}

