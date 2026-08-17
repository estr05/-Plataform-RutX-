<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole — middleware `role:<rol[,rol...]>`.
 *
 * Primera mitad de la autorización doble (guidelines §1.3): valida localmente
 * que el usuario de sesión tenga al menos uno de los roles permitidos antes de
 * llegar a la ruta. La API remota valida el mismo permiso en segundo plano;
 * ocultar o bloquear la UI no sustituye la validación del Sincronizador.
 *
 * Los roles provienen de la sesión cifrada, poblada con los claims del JWT web
 * (contrato v2 §5.1): session('user.roles') = ['administrador'|'supervisor'|'lector'].
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRoles = (array) session('user.roles', []);

        if (count(array_intersect($userRoles, $roles)) === 0) {
            abort(403, __('No tienes permiso para acceder a esta sección.'));
        }

        return $next($request);
    }
}
