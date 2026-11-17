<?php

namespace App\Providers;

use App\Services\PricingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cache de ofertas activas por request (se reinicia en cada request/test)
        $this->app->scoped(PricingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Operaciones del carrito (agregar/actualizar/eliminar/ver)
        RateLimiter::for('carrito', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Aplicar cupón: limita la fuerza bruta de códigos
        RateLimiter::for('cupones', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Creación de pedidos (web y WhatsApp): reservan stock, evitar abuso
        RateLimiter::for('pedidos', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(30)->by('h:'.$request->ip()),
            ];
        });

        // Login del panel: por email + IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
