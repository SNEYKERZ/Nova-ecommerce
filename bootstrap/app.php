<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/admin/login');

        $middleware->web(append: [
            ResolveTenant::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'admin.role' => EnsureAdminRole::class,
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\RuntimeException $e, Request $request) {
            // Atrapar el error cuando el hash almacenado no es bcrypt
            if (str_contains($e->getMessage(), 'does not use the Bcrypt algorithm')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Credenciales incorrectas.',
                        'errors' => ['email' => ['Credenciales incorrectas.']],
                    ], 422);
                }

                throw ValidationException::withMessages([
                    'email' => 'Credenciales incorrectas.',
                ]);
            }
        });
    })->create();
