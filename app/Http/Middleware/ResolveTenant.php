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
     * Detecta el tenant desde el subdomain o dominio y lo establece en el TenantManager.
     * Las rutas públicas (tienda) siempre requieren un tenant válido.
     * Las rutas de super admin no requieren tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Usar el binding correcto - el servicio registrado como singleton
        $tenantManager = app(TenantManager::class);

        // Rutas que NO requieren tenant (son del sistema, no de tiendas)
        $noTenantRoutes = [
            'admin/login',
            'admin/logout',
        ];

        // Si es ruta del sistema (super-admin), continuar sin tenant
        $path = $request->path();
        if ($request->is('super-admin') || in_array($path, $noTenantRoutes)) {
            return $next($request);
        }

        // Detectar si el usuario logueado es super_admin
        if ($user = $request->user()) {
            if ($user->role === 'super_admin') {
                $tenantManager->setSuperAdmin(true);
                // Super admin puede acceder a super-admin sin store
                if ($request->is('super-admin')) {
                    return $next($request);
                }
            }
        }

        // Resolver el tenant desde el request (para tiendas)
        $store = $tenantManager->resolveFromRequest($request);
        
        // Establecer el store en el TenantManager
        $tenantManager->setStore($store);

        // Compartir el store con las vistas
        if ($store) {
            view()->share('tenant', $store);
            view()->share('currentStore', $store);
        }

        // Verificar que existe un tenant válido para las rutas de tienda
        // Super admin puede acceder aunque no haya store
        if (!$store && !$tenantManager->isSuperAdmin()) {
            abort(404, 'Tienda no encontrada');
        }

        return $next($request);
    }
}