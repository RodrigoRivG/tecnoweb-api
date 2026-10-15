<?php

return [

    'rol_no_autorizado' => 'Tu rol no tiene acceso a esta sección.',

    'registro' => [
        'menor_de_edad' => 'Debes ser mayor de 18 años para registrarte.',
        'ci_con_cuenta' => 'Ya existe una cuenta con este CI. Inicia sesión.',
        'ci_sin_cuenta' => 'Este CI ya está registrado en el sistema. Comunícate con la consultora para activar tu cuenta.',
        'carrera_inactiva' => 'La carrera seleccionada no está disponible.',
        'area_otra_carrera' => 'El área de preferencia no pertenece a la carrera seleccionada.',
        'apellido_requerido' => 'Indica al menos un apellido.',
        'ppa_decimales' => 'El PPA puede tener hasta 2 decimales.',
    ],

    'postulacion' => [
        'no_disponible' => 'La convocatoria no está abierta.',
        'otra_carrera' => 'La convocatoria no corresponde a tu carrera.',
        'duplicada' => 'Ya postulaste a esta convocatoria.',
        'ppa_insuficiente' => 'Tu PPA (:ppa) no alcanza el mínimo de :minimo exigido por la facultad.',
    ],

    'documento' => [
        'tipo_no_requerido' => 'La convocatoria no solicita este tipo de documento.',
        'postulacion_cerrada' => 'La postulación ya no admite documentos.',
        'requisitos_cerrados' => 'La etapa de requisitos ya fue revisada; no se pueden cambiar los documentos.',
    ],

];
