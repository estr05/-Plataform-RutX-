<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => 'nobody@rutx.test',
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        // El sexto intento en el mismo minuto (mismo correo + IP) es 429.
        $this->post(route('login.attempt'), [
            'email' => 'nobody@rutx.test',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}
