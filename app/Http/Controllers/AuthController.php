<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * AuthController — autenticación web local (sprint/2).
 *
 * Usuarios propios en BD (tabla users) con el guard web de Laravel.
 * La integración al Sincronizador usa client-credentials por separado
 * (App\Services\RutxApiClient); el login local no depende de la API.
 *
 * Seguridad (guidelines §1.2):
 *  - Rotación de ID de sesión al iniciar y cerrar sesión.
 *  - Sesión server-side (driver database); en cookie solo el identificador.
 *  - Logout por POST con CSRF; nunca por GET.
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => __('Las credenciales no coinciden con nuestros registros.'),
                ])
                ->onlyInput('email');
        }

        // Prevenir fijación de sesión: nuevo ID tras autenticar.
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Cierra la sesión del usuario autenticado.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
