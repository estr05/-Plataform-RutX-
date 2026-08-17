<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_administrador_can_access_transfer_view(): void
    {
        $this->withoutVite();

        $this->actingAs($this->userWithRole('administrador'))
            ->get('/customers/transfer')
            ->assertOk();
    }

    public function test_lector_without_transfer_permission_gets_403(): void
    {
        $this->actingAs($this->userWithRole('lector'))
            ->get('/customers/transfer')
            ->assertForbidden();
    }

    public function test_lector_can_access_read_views(): void
    {
        $this->withoutVite();

        $this->actingAs($this->userWithRole('lector'))
            ->get('/customers')
            ->assertOk();
    }

    public function test_lector_cannot_access_settings_module(): void
    {
        $this->actingAs($this->userWithRole('lector'))
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_home_hides_modules_without_permission(): void
    {
        $this->withoutVite();

        // Lector: Configuración no aparece (ni en topbar ni en cards).
        $this->actingAs($this->userWithRole('lector'))
            ->get('/')
            ->assertOk()
            ->assertDontSee('Configuración');

        // Administrador: sí la ve.
        $this->actingAs($this->userWithRole('administrador'))
            ->get('/')
            ->assertOk()
            ->assertSee('Configuración');
    }

    public function test_sidebar_hides_views_without_permission(): void
    {
        $this->withoutVite();

        // En /customers, el lector no ve la vista de Traspaso en el sidebar.
        $this->actingAs($this->userWithRole('lector'))
            ->get('/customers')
            ->assertOk()
            ->assertSee('Clientes')
            ->assertDontSee('Traspaso de cliente');

        // El administrador sí la ve.
        $this->actingAs($this->userWithRole('administrador'))
            ->get('/customers')
            ->assertOk()
            ->assertSee('Traspaso de cliente');
    }
}
