<?php

/*
|--------------------------------------------------------------------------
| Restricciones de la BD → campo y mensaje (spec 02, sección 6.6)
|--------------------------------------------------------------------------
|
| Cubre las violaciones que la validación no detecta por concurrencia.
|
*/

return [

    'restricciones' => [
        'uq_usuario_correo' => ['campo' => 'correo', 'mensaje' => 'El correo ya está registrado.'],
        'uq_persona_ci' => ['campo' => 'ci_numero', 'mensaje' => 'El número de CI ya está registrado.'],
        'uq_persona_usuario' => ['campo' => 'correo', 'mensaje' => 'La cuenta ya tiene una persona asociada.'],
        'uq_egresado_registro' => ['campo' => 'registro_universitario', 'mensaje' => 'El registro universitario ya está registrado.'],
        'uq_egresado_persona_carrera' => ['campo' => 'carrera_id', 'mensaje' => 'Ya estás registrado en esta carrera.'],
        'uq_postulacion_proceso_persona' => ['campo' => 'proceso_id', 'mensaje' => 'Ya postulaste a esta convocatoria.'],
        'uq_documento_vigente' => ['campo' => 'archivo', 'mensaje' => 'Se subió otro documento del mismo tipo al mismo tiempo. Intenta de nuevo.'],
        'ck_persona_apellido' => ['campo' => 'apellido_paterno', 'mensaje' => 'Indica al menos un apellido.'],
        'ck_egresado_ppa' => ['campo' => 'ppa', 'mensaje' => 'El PPA debe estar entre 0 y 100.'],
        'ck_egresado_anio' => ['campo' => 'anio_egreso', 'mensaje' => 'El año de egreso no es válido.'],
    ],

    'generico' => [
        'unico' => 'El registro ya existe.',
        'referencia' => 'Referencia inválida.',
        'en_uso' => 'No se puede eliminar porque otros registros dependen de él.',
        'valor' => 'Valor no permitido.',
    ],

];
