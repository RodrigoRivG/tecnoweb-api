<?php

namespace App\Http\Requests\Publico;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de los listados de la página de bienvenida.
 */
class FiltroPublicoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'facultad_id' => ['nullable', 'integer'],
            'carrera_id' => ['nullable', 'integer'],
            'solo_top' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
