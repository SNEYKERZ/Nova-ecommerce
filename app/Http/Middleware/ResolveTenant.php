<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la tienda (tenant) del request.
 *
 * IMPORTANTE: este middleware está en la lista de prioridad ANTES de
 * SubstituteBindings (ver bootstrap/app.php) para que el route model binding
 * ya aplique el global scope de HasTenant. Si corriera después, un admin podría
 * cargar por ID modelos de otra tienda.
 *
 * Rutas que NO requieren tenant:
 * - super-admin/* (gestión global)
 * - admin/login, admin/logout
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);

        // Estado limpio por request (workers persistentes / tests)
        $tenantManager->clear();

        // 1. Rutas del sistema - no requieren tenant
        if ($request->is('super-admin', 'super-admin/*', 'admin/login', 'admin/logout', 'sanctum/csrf-cookie')) {
            return $next($request);
        }

        // Resolver el usuario ANTES de fijar la tienda: User usa HasTenant y,
        // si se resolviera después, un super admin (store_id NULL) quedaría
        // filtrado por el scope y "deslogueado" en rutas de tienda.
        $user = $request->user();

        if ($user && $user->role === 'super_admin') {
            $tenantManager->setSuperAdmin(true);
        }

        $isLocal = $tenantManager->isLocalRequest($request);

        // En desarrollo local, persistir ?store= a la sesión para que sobreviva redirects
        if ($isLocal && $request->has('store') && $request->hasSession()) {
            $storeOverride = $request->query('store');
            if (is_string($storeOverride) && $storeOverride !== '') {
                $request->session()->put('tenant_store', $storeOverride);
            } else {
                $request->session()->forget('tenant_store');
            }
        }

        $store = $tenantManager->resolveFromRequest($request);

        // Un admin de tienda que entra por un host sin tienda (dominio central,
        // p. ej. tras una impersonación) opera siempre sobre SU tienda.
        if (! $store && $user && $user->role === 'admin' && $user->store_id) {
            $store = Store::where('id', $user->store_id)->where('activo', true)->first();
        }

        $tenantManager->setStore($store);

        if ($store) {
            view()->share('tenant', $store);
            view()->share('currentStore', $store);

            return $next($request);
        }

        // Sin tienda: sólo se permite en localhost de desarrollo o para el
        // super admin (que en rutas /admin será redirigido por EnsureAdminRole).
        if ($isLocal || $tenantManager->isSuperAdmin()) {
            return $next($request);
        }

        abort(404, 'Tienda no encontrada');
    }
}
