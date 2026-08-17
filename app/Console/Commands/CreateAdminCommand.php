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
                            {--email= : Correo del administrador (o env ADMIN_EMAIL)}
                            {--password= : Contraseña (o env ADMIN_PASSWORD). Mínimo 12 caracteres}';

    protected $description = 'Crea el primer usuario administrador sin credenciales conocidas.';

    public function handle(): int
    {
        $email = $this->option('email') ?? env('ADMIN_EMAIL');
        $password = $this->option('password') ?? env('ADMIN_PASSWORD');
        $name = $this->option('name') ?? 'Administrador';

        if (! $email || ! $password) {
            $this->error('Se requieren --email y --password (o ADMIN_EMAIL/ADMIN_PASSWORD en el entorno).');

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

        $this->info("Administrador creado: {$user->email}");

        return self::SUCCESS;
    }
}
