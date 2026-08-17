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
     * Usuario administrador de desarrollo (SOLO local/testing).
     *
     * Credenciales conocidas: admin@rutx.test / password.
     * Se bloquea expresamente fuera de local/testing, incluido staging:
     * un entorno alcanzable jamás debe sembrar credenciales predecibles.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DevelopmentSeeder solo se ejecuta en local/testing; abortado.');

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
