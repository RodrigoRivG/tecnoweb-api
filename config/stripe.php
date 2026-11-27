<?php

return [
    'secret' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'moneda' => env('STRIPE_MONEDA', 'usd'),
    'precio_centavos_por_vacante' => (int) env('STRIPE_PRECIO_CENTAVOS_VACANTE', 1500),
    'precio_centavos_registro' => (int) env('STRIPE_PRECIO_REGISTRO_CENTAVOS', 1500),
    'simular_sin_clave' => (bool) env('STRIPE_SIMULAR_SIN_CLAVE', true),
];