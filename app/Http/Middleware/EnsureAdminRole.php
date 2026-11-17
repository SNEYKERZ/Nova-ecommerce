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

        if (! $user) {
            return redirect('/admin/login');
        }

        $isSuperAdminArea = $request->is('super-admin', 'super-admin/*');

        // Super admin puede acceder a todo
        if ($user->role === 'super_admin') {
            // En /admin sin tienda resuelta no hay sobre qué operar: sin este
            // corte los queries quedarían sin filtro de tienda (todas las tiendas).
            if (! $isSuperAdminArea && ! app(TenantManager::class)->hasStore()) {
                if ($request->expectsJson()) {
                    abort(409, 'Selecciona una tienda (o usa "Entrar como") para administrarla.');
                }

                return redirect('/super-admin');
            }

            return $next($request);
        }

        if ($user->role !== 'admin') {
            abort(403, 'No tienes permisos para acceder al panel administrativo.');
        }

        // Admin de tienda: nunca accede al área super-admin (incluye sub-rutas)
        if ($isSuperAdminArea) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        // Verificar que el usuario tenga un store asignado
        if (! $user->store_id) {
            abort(403, 'Tu cuenta no tiene una tienda asignada.');
        }

        // La tienda resuelta debe existir y coincidir con la del usuario.
        // Sin tienda resuelta los queries no se filtrarían por tenant.
        $currentStore = app(TenantManager::class)->getStore();

        if (! $currentStore || (int) $currentStore->id !== (int) $user->store_id) {
            abort(403, 'No tienes acceso a esta tienda.');
        }

        return $next($request);
    }
}
