<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Usuario administrador de desarrollo (solo local/testing).
     *
     * Credenciales conocidas: admin@rutx.test / password.
     * Nunca ejecutar en staging/producción: el guard de abajo lo bloquea.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DevelopmentSeeder no se ejecuta en producción.');

            return;
        }

        $user = User::factory()->create([
            'name' => 'Administrador RutX (dev)',
            'email' => 'admin@rutx.test',
        ]);

        // role no es mass-assignable (P1): se fija explícitamente.
        $user->forceFill(['role' => 'administrador'])->save();
    }
}
