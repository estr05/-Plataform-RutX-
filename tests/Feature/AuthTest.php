<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('secret-password'),
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/customers')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->makeUser();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $this->makeUser();

        $this->post(route('login.attempt'), [
            'email' => 'admin@rutx.test',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_session_id_is_regenerated_after_login(): void
    {
        $user = $this->makeUser();

        $this->get('/login');
        $before = session()->getId();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_post(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('logout'))->assertStatus(405);
    }

    public function test_authenticated_user_can_access_modules(): void
    {
        $user = $this->makeUser();
        $this->withoutVite();

        $this->actingAs($user)
            ->get('/customers')
            ->assertOk();
    }
}
