<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'user:create-admin
                            {--name= : Nombre visible (default: Administrador)}
                            {--email= : Correo del administrador (o env ADMIN_EMAIL)}';

    protected $description = 'Crea el primer usuario administrador sin credenciales conocidas.';

    public function handle(): int
    {
        $email = $this->option('email') ?? env('ADMIN_EMAIL');
        $name = $this->option('name') ?? 'Administrador';

        if (! $email) {
            $this->error('Se requiere --email (o ADMIN_EMAIL en el entorno).');

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            $this->error('No se pudo obtener una contraseña. Ejecútelo de forma interactiva o defina ADMIN_PASSWORD como secreto del entorno.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ], [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // cast 'hashed' en el modelo
        ]);

        // role no es mass-assignable (P1): solo este comando/servicio lo fija.
        $user->forceFill(['role' => 'administrador'])->save();

        // Los logs y la salida muestran solo el correo, nunca la contraseña.
        $this->info("Administrador creado: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * Contraseña del primer administrador (sprint/3):
     *  - Interactivo: se solicita por entrada secreta y se confirma; nunca se
     *    acepta como argumento de terminal (fuera del historial de shell).
     *  - No interactivo: SOLO vía ADMIN_PASSWORD inyectado como secreto del
     *    entorno; nunca en .env.example, scripts versionados ni salida.
     */
    private function resolvePassword(): ?string
    {
        if ($this->option('no-interaction')) {
            $password = env('ADMIN_PASSWORD');

            return is_string($password) && $password !== '' ? $password : null;
        }

        $password = $this->secret('Contraseña del administrador (mínimo 12 caracteres)');
        $confirmation = $this->secret('Confirme la contraseña');

        if ($password !== $confirmation) {
            $this->error('Las contraseñas no coinciden.');

            return null;
        }

        return $password;
    }
}
