<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarLimitesDePeticiones();
    }

    /**
     * Límites de la API (spec 01, sección 3.3).
     */
    private function configurarLimitesDePeticiones(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('correo')).'|'.$request->ip()));

        RateLimiter::for('registro', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('publico', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('autenticado', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->user()?->getAuthIdentifier()));
    }
}
