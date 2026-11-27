<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CorsTest extends TestCase
{
    #[TestWith([null, 'https://www.example.test', true])]
    #[TestWith(['https://www.example.test, https://example.test', 'https://www.example.test', true])]
    #[TestWith(['https://www.example.test, https://example.test', 'https://example.test', true])]
    #[TestWith(['https://www.example.test, https://example.test', 'https://otro.example.test', false])]
    #[TestWith(['http://localhost:5173', 'http://localhost:5173', true])]
    #[TestWith(['http://localhost:5173', 'http://localhost:5174', false])]
    public function test_preflight_del_login_permite_solo_los_origenes_configurados(?string $origins, string $origin, bool $allowed): void
    {
        $previousEnv = $_ENV;
        $previousServer = $_SERVER;
        $previousOrigins = getenv('FRONTEND_ORIGINS');

        try {
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
            $_ENV['FRONTEND_URL'] = $_SERVER['FRONTEND_URL'] = 'https://www.example.test/inf513/grupo18sa/proyecto2';

            if ($origins === null) {
                unset($_ENV['FRONTEND_ORIGINS'], $_SERVER['FRONTEND_ORIGINS']);
                putenv('FRONTEND_ORIGINS');
            } else {
                $_ENV['FRONTEND_ORIGINS'] = $_SERVER['FRONTEND_ORIGINS'] = $origins;
            }

            config(['cors' => require config_path('cors.php')]);

            $response = $this->options('/api/v1/auth/login', [], [
                'Origin' => $origin,
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'content-type,authorization',
            ]);

            $response->assertNoContent();

            if ($allowed) {
                $response->assertHeader('Access-Control-Allow-Origin', $origin);
            } else {
                $this->assertNotSame($origin, $response->headers->get('Access-Control-Allow-Origin'));
                $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
            }
        } finally {
            $_ENV = $previousEnv;
            $_SERVER = $previousServer;
            putenv($previousOrigins === false ? 'FRONTEND_ORIGINS' : 'FRONTEND_ORIGINS='.$previousOrigins);
        }
    }
}
