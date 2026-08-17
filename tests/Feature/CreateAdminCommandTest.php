<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_without_known_credentials(): void
    {
        $this->artisan('user:create-admin', [
            '--email' => 'admin@rutx.mx',
            '--password' => 'S3gura-Larga-2026',
            '--name' => 'Admin Principal',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@rutx.mx',
            'role' => 'administrador',
        ]);
    }

    public function test_command_rejects_short_password(): void
    {
        $this->artisan('user:create-admin', [
            '--email' => 'admin@rutx.mx',
            '--password' => 'corta',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@rutx.mx']);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@rutx.mx']);

        $this->artisan('user:create-admin', [
            '--email' => 'admin@rutx.mx',
            '--password' => 'S3gura-Larga-2026',
        ])->assertExitCode(1);
    }

    public function test_command_requires_email_and_password(): void
    {
        $this->artisan('user:create-admin')->assertExitCode(1);
    }

    public function test_development_seeder_creates_dev_admin_only_outside_production(): void
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DevelopmentSeeder'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@rutx.test',
            'role' => 'administrador',
        ]);
    }
}
