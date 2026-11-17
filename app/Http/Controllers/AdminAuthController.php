<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('auth/AdminLoginPage');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->upgradeLegacyMd5Password($credentials['email'], $credentials['password']);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! in_array($user->role, ['admin', 'super_admin'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no tiene permisos de administrador.',
            ]);
        }

        // Redirigir según el rol
        if ($user->role === 'super_admin') {
            return redirect()->intended('/super-admin');
        }

        return redirect()->intended('/admin');
    }

    /**
     * Cuentas heredadas con la contraseña en MD5 (no bcrypt) no podían iniciar
     * sesión nunca. Si la contraseña escrita coincide con el MD5 guardado, se
     * re-hashea con bcrypt antes del intento normal de login.
     */
    private function upgradeLegacyMd5Password(string $email, string $password): void
    {
        $user = User::allStores()->where('email', $email)->first();
        $stored = $user?->getAuthPassword();

        if (! $user || ! is_string($stored) || ! preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return;
        }

        if (! hash_equals(strtolower($stored), md5($password))) {
            return;
        }

        $user->forceFill(['password' => Hash::make($password)])->saveQuietly();
        Log::info('Contraseña MD5 heredada migrada a bcrypt al iniciar sesión', ['user_id' => $user->id]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Volver a la cuenta de super admin tras una impersonación.
     *
     * Requiere sesión iniciada (middleware auth). El ID del impersonador vive
     * sólo en la sesión del servidor y además se verifica que siga siendo
     * super_admin; si no, se cierra la sesión por completo.
     */
    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return redirect('/admin');
        }

        // Sin global scope: el super admin no pertenece a ninguna tienda
        $impersonator = User::withoutGlobalScopes()->find($impersonatorId);

        if (! $impersonator || $impersonator->role !== 'super_admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login');
        }

        $request->session()->forget('impersonator_id');
        Auth::login($impersonator);
        $request->session()->regenerate();

        return redirect('/super-admin');
    }
}
