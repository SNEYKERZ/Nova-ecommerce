<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * Rutas que NO requieren tenant:
     * - super-admin (gestión global)
     - auth (login/logout)
     - api/* (excepto admin operations)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);
        $path = $request->path();

        // 1. Rutas del sistema - no requieren tenant
        if ($request->is('super-admin*') || 
            $request->is('admin/login') || 
            $request->is('admin/logout') ||
            $request->is('sanctum/csrf-cookie')) {
            return $next($request);
        }

        // 2. Rutas API públicas - pueden ejecutarse sin store
        // El trait HasTenant filtra automáticamente por store si existe
        if (str_starts_with($path, 'api/')) {
            $store = $tenantManager->resolveFromRequest($request);
            $tenantManager->setStore($store);
            
            if ($store) {
                view()->share('tenant', $store);
                view()->share('currentStore', $store);
            }
            
            return $next($request);
        }

        // 3. Rutas web (frontend) - requieren store válido
        // Detectar si el usuario logueado es super_admin
        if ($user = $request->user()) {
            if ($user->role === 'super_admin') {
                $tenantManager->setSuperAdmin(true);
                return $next($request);
            }
        }

        // Resolver el tenant desde el request
        $store = $tenantManager->resolveFromRequest($request);
        $tenantManager->setStore($store);

        if ($store) {
            view()->share('tenant', $store);
            view()->share('currentStore', $store);
        }

        // En desarrollo local (localhost), permitir acceso aunque no haya tienda
        // para poder crear la primera tienda
        $isLocal = in_array($request->getHost(), ['localhost', '127.0.0.1']) || 
                  str_starts_with($request->getHost(), 'localhost:');
        
        if ($isLocal) {
            return $next($request);
        }

        // Frontend requiere store cuando no es localhost
        if (!$store && !$tenantManager->isSuperAdmin()) {
            abort(404, 'Tienda no encontrada');
        }

        return $next($request);
    }
}