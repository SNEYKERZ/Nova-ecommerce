<?php

use App\Exceptions\CheckoutException;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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

        // El tenant debe resolverse ANTES del route model binding para que el
        // global scope de HasTenant se aplique a {producto}, {pedido}, {cupon}...
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveTenant::class);

        $middleware->alias([
            'admin.role' => EnsureAdminRole::class,
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Token CSRF caducado (419) en una petición Inertia: sin esto el formulario
        // no hace nada y no muestra error. Volver a la página trae un token nuevo,
        // así que el siguiente intento funciona.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419 || ! $request->header('X-Inertia')) {
                return $response;
            }

            $message = 'Tu sesión expiró. Vuelve a intentarlo.';
            $key = $request->is('admin/login') ? 'email' : 'csrf';

            return back()->withErrors([$key => $message])->withInput($request->except('password', 'password_confirmation'));
        });

        // Errores de negocio del checkout (stock, producto no disponible...)
        $exceptions->render(function (CheckoutException $e, Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->renderable(function (RuntimeException $e, Request $request) {
            // Sólo en el login: un hash almacenado que no es bcrypt (usuarios
            // legacy) se presenta como credenciales incorrectas. Fuera del login
            // el error sigue su curso normal (no se oculta).
            if (! $request->is('admin/login')
                || ! str_contains($e->getMessage(), 'does not use the Bcrypt algorithm')) {
                return null;
            }

            Log::warning('Login con hash de contraseña no bcrypt', ['email' => $request->input('email')]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Credenciales incorrectas.',
                    'errors' => ['email' => ['Credenciales incorrectas.']],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->onlyInput('email');
        });
    })->create();
