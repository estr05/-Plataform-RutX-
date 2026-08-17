<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserRoleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = User::query()->create([
            'name' => 'Prueba',
            'email' => 'masa@rutx.test',
            'password' => 'password',
            'role' => 'administrador',
        ]);

        // El atributo no se setea en el modelo en memoria ni persiste como administrador.
        $this->assertNull($user->role);
        $this->assertSame('lector', $user->fresh()->role, 'El rol no debe poder asignarse por mass assignment.');
    }

    public function test_administrador_can_change_role(): void
    {
        $admin = $this->userWithRole('administrador');
        $user = User::factory()->create();

        app(UserRoleService::class)->changeRole($admin, $user, 'supervisor');

        $this->assertSame('supervisor', $user->fresh()->role);
    }

    public function test_lector_cannot_change_role(): void
    {
        $lector = $this->userWithRole('lector');
        $user = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(UserRoleService::class)->changeRole($lector, $user, 'administrador');
    }

    public function test_invalid_role_is_rejected(): void
    {
        $admin = $this->userWithRole('administrador');
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(UserRoleService::class)->changeRole($admin, $user, 'root');
    }
}
