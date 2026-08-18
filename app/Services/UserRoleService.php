<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * UserRoleService — única vía autorizada para cambiar roles (P1).
 *
 * El campo role NO es mass-assignable (Fuera de User::$fillable). Solo este
 * servicio (y el comando user:create-admin) lo modifica:
 *  - Exige el permiso de escritura 'config.roles.write' del actor.
 *  - Valida el rol contra config('permissions.roles'); nunca cadenas libres.
 */
class UserRoleService
{
    public function changeRole(User $actor, User $user, string $role): User
    {
        if (! $actor->can('config.roles.write')) {
            throw new AuthorizationException('No tienes permiso para cambiar roles.');
        }

        $validRoles = array_keys(config('permissions.roles', []));

        if (! in_array($role, $validRoles, true)) {
            throw ValidationException::withMessages([
                'role' => 'Rol inválido. Permitidos: '.implode(', ', $validRoles).'.',
            ]);
        }

        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
