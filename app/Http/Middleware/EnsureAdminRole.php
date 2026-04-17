<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/admin/login');
        }

        // Super admin puede acceder a todo
        if ($user->role === 'super_admin') {
            // Redirigir al dashboard de super admin
            if ($request->is('admin') && !$request->is('super-admin')) {
                // Si accede a /admin y es super admin, puede seguir o redirigir
            }
            return $next($request);
        }

        // Admin normal debe tener un store asignado
        if ($user->role === 'admin') {
            // No puede acceder a super-admin
            if ($request->is('super-admin')) {
                abort(403, 'No tienes acceso a esta sección.');
            }

            // Verificar que el usuario tenga un store asignado
            if (!$user->store_id) {
                abort(403, 'Tu cuenta no tiene una tienda asignada.');
            }

            // Verificar que el store actual coincida con el del usuario
            $tenantManager = app(TenantManager::class);
            $currentStore = $tenantManager->getStore();

            if ($currentStore && $currentStore->id !== $user->store_id) {
                abort(403, 'No tienes acceso a esta tienda.');
            }
        }

        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            abort(403, 'No tienes permisos para acceder al panel administrativo.');
        }

        return $next($request);
    }
}
