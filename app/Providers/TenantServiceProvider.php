<?php

namespace App\Providers;

use App\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar TenantManager como singleton (persiste durante el request)
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager();
        });

        // 注册别名 para acceso rápido via app('tenant.store')
        $this->app->singleton('tenant.store', function ($app) {
            return $app->make(TenantManager::class)->getStore();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}