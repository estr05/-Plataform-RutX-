<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_with_interactive_secret_capture(): void
    {
        $this->artisan('user:create-admin', [
            '--email' => 'admin@rutx.mx',
            '--name' => 'Admin Principal',
        ])
            ->expectsQuestion('Contraseña del administrador (mínimo 12 caracteres)', 'S3gura-Larga-2026')
            ->expectsQuestion('Confirme la contraseña', 'S3gura-Larga-2026')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@rutx.mx',
            'role' => 'administrador',
        ]);
    }

    public function test_command_rejects_password_mismatch(): void
    {
        $this->artisan('user:create-admin', ['--email' => 'admin@rutx.mx'])
            ->expectsQuestion('Contraseña del administrador (mínimo 12 caracteres)', 'S3gura-Larga-2026')
            ->expectsQuestion('Confirme la contraseña', 'Otra-Larga-2026')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@rutx.mx']);
    }

    public function test_command_rejects_short_password_interactively(): void
    {
        $this->artisan('user:create-admin', ['--email' => 'admin@rutx.mx'])
            ->expectsQuestion('Contraseña del administrador (mínimo 12 caracteres)', 'corta')
            ->expectsQuestion('Confirme la contraseña', 'corta')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@rutx.mx']);
    }

    public function test_command_creates_admin_with_env_secret_non_interactive(): void
    {
        putenv('ADMIN_PASSWORD=S3gura-Larga-2026');

        try {
            $this->artisan('user:create-admin', [
                '--email' => 'admin@rutx.mx',
                '--no-interaction' => true,
            ])->assertExitCode(0);
        } finally {
            putenv('ADMIN_PASSWORD');
        }

        $this->assertDatabaseHas('users', [
            'email' => 'admin@rutx.mx',
            'role' => 'administrador',
        ]);
    }

    public function test_command_rejects_non_interactive_run_without_env_secret(): void
    {
        putenv('ADMIN_PASSWORD');

        $this->artisan('user:create-admin', [
            '--email' => 'admin@rutx.mx',
            '--no-interaction' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@rutx.mx']);
    }

    public function test_command_rejects_short_env_secret_non_interactive(): void
    {
        putenv('ADMIN_PASSWORD=corta');

        try {
            $this->artisan('user:create-admin', [
                '--email' => 'admin@rutx.mx',
                '--no-interaction' => true,
            ])->assertExitCode(1);
        } finally {
            putenv('ADMIN_PASSWORD');
        }

        $this->assertDatabaseMissing('users', ['email' => 'admin@rutx.mx']);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@rutx.mx']);

        $this->artisan('user:create-admin', ['--email' => 'admin@rutx.mx'])
            ->expectsQuestion('Contraseña del administrador (mínimo 12 caracteres)', 'S3gura-Larga-2026')
            ->expectsQuestion('Confirme la contraseña', 'S3gura-Larga-2026')
            ->assertExitCode(1);
    }

    public function test_command_requires_email(): void
    {
        $this->artisan('user:create-admin')->assertExitCode(1);
    }

    public function test_command_output_never_contains_the_password(): void
    {
        putenv('ADMIN_PASSWORD=S3gura-Larga-2026');

        try {
            $this->artisan('user:create-admin', [
                '--email' => 'admin@rutx.mx',
                '--no-interaction' => true,
            ])
                ->expectsOutput('Administrador creado: admin@rutx.mx')
                ->doesntExpectOutput('S3gura-Larga-2026')
                ->assertExitCode(0);
        } finally {
            putenv('ADMIN_PASSWORD');
        }
    }

    public function test_development_seeder_creates_dev_admin_in_testing(): void
    {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DevelopmentSeeder'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@rutx.test',
            'role' => 'administrador',
        ]);
    }

    public function test_development_seeder_is_blocked_in_staging(): void
    {
        $this->app['env'] = 'staging';

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DevelopmentSeeder'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_development_seeder_is_blocked_in_production(): void
    {
        $this->app['env'] = 'production';

        // --force evita la confirmación de Laravel para seeders en producción;
        // el guard del propio DevelopmentSeeder debe abortar igual.
        $this->artisan('db:seed', [
            '--class' => 'Database\\Seeders\\DevelopmentSeeder',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }
}
