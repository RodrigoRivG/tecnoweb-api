<?php

namespace App\Http\Requests\Egresado;

use Illuminate\Foundation\Http\FormRequest;

class PostularRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'proceso_id' => ['required', 'integer'],
        ];
    }
}
