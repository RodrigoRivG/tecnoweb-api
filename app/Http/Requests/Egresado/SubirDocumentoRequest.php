<?php

namespace App\Http\Requests\Egresado;

use App\Models\TipoDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirDocumentoRequest extends FormRequest
{
    /**
     * Tamaño máximo del archivo en kilobytes (5 MB).
     */
    public const TAMANO_MAXIMO_KB = 5120;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        $formatos = $this->input('tipo_documento') === TipoDocumento::FOTOGRAFIA ? 'jpg,jpeg,png' : 'pdf,jpg,jpeg,png';

        return [
            'tipo_documento' => ['required', 'string', Rule::exists(TipoDocumento::class, 'codigo')],
            'archivo' => ['required', 'file', 'max:'.self::TAMANO_MAXIMO_KB, 'mimes:'.$formatos],
        ];
    }
}
