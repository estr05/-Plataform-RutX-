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
     * Datos de desarrollo (admin@rutx.test) viven en DevelopmentSeeder (solo
     * local/testing; bloqueado en staging/producción) y se ejecutan
     * explícitamente (php artisan db:seed --class=DevelopmentSeeder).
     * El primer administrador real se crea con:
     *   php artisan user:create-admin --email=...
     * La contraseña se solicita por entrada secreta de forma interactiva o se
     * inyecta como ADMIN_PASSWORD en el entorno (nunca como argumento ni en
     * archivos versionados).
     */
    public function run(): void
    {
        // Datos de catálogo no sensibles (si existieran) irían aquí.
    }
}
