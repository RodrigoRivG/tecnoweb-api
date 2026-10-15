<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mensajes de validación
    |--------------------------------------------------------------------------
    |
    | Reglas usadas por la API. Las que falten caen al idioma de respaldo.
    |
    */

    'accepted' => 'Debes aceptar :attribute.',
    'array' => 'El campo :attribute debe ser una lista.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_format' => 'El campo :attribute debe tener el formato :format.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'enum' => 'El valor de :attribute no es válido.',
    'exists' => 'El valor de :attribute no es válido.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'in' => 'El valor de :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'file' => 'El :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'mimes' => 'El :attribute debe ser de tipo: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'mixed' => 'La :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'La :attribute debe contener al menos un número.',
        'symbols' => 'La :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'La :attribute apareció en una filtración de datos. Elige otra.',
    ],
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_without' => 'El campo :attribute es obligatorio cuando no se indica :values.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El :attribute ya está registrado.',
    'uploaded' => 'No se pudo subir :attribute.',

    /*
    |--------------------------------------------------------------------------
    | Nombres de los atributos
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'acepta_publicacion_perfil' => 'publicación del perfil',
        'acepta_tratamiento_datos' => 'la política de tratamiento de datos',
        'anio_egreso' => 'año de egreso',
        'apellido_materno' => 'apellido materno',
        'apellido_paterno' => 'apellido paterno',
        'archivo' => 'archivo',
        'area_preferencia_id' => 'área de preferencia',
        'carrera_id' => 'carrera',
        'ci_complemento' => 'complemento del CI',
        'ci_expedido' => 'departamento de expedición',
        'ci_numero' => 'número de CI',
        'correo' => 'correo',
        'facultad_id' => 'facultad',
        'fecha_nacimiento' => 'fecha de nacimiento',
        'nombres' => 'nombres',
        'password' => 'contraseña',
        'ppa' => 'PPA',
        'proceso_id' => 'convocatoria',
        'registro_universitario' => 'registro universitario',
        'solo_top' => 'solo top',
        'telefono' => 'teléfono',
        'tipo_documento' => 'tipo de documento',
    ],

];
