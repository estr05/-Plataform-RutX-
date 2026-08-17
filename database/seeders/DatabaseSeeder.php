<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeder productivo — NO crea usuarios con credenciales conocidas.
     *
     * Datos de desarrollo (admin@rutx.test) viven en DevelopmentSeeder y se
     * ejecutan explícitamente (php artisan db:seed --class=DevelopmentSeeder).
     * El primer administrador real se crea con:
     *   php artisan user:create-admin --email=... --password=...
     */
    public function run(): void
    {
        // Datos de catálogo no sensibles (si existieran) irían aquí.
    }
}
