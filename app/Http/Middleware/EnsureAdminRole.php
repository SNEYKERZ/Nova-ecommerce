<?php

namespace App\Http\Middleware;

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

        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            abort(403, 'No tienes permisos para acceder al panel administrativo.');
        }

        return $next($request);
    }
}
