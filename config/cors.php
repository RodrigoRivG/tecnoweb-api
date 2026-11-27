<?php

$frontendOrigins = env('FRONTEND_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173'));

$allowedOrigins = array_values(array_unique(array_filter(array_map(
    static function (string $url): ?string {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])
            || ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '');
    },
    explode(',', $frontendOrigins),
))));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Frontend React + Vite; varios orígenes separados por coma
    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => env('APP_ENV', 'production') === 'local'
        ? ['#^http://(localhost|127\.0\.0\.1):\d+$#']
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
