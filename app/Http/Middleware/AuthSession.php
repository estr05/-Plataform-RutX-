<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthSession — middleware `auth.session`.
 *
 * Protege las rutas web autenticadas de RutX Web: exige que exista el JWT web
 * en la sesión cifrada de Laravel (session('api_token')). El token nunca se
 * guarda en localStorage ni en cookies legibles (guidelines §1.2).
 */
class AuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('api_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
