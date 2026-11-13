<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
        {name : Nombre del administrador}
        {email : Correo electrónico}
        {--password= : Contraseña (si se omite, se pedirá por consola)}';

    protected $description = 'Crea una cuenta de administrador (o convierte en administrador una cuenta existente).';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if (! $this->confirm("Ya existe una cuenta con {$email}. ¿Convertirla en administrador?")) {
                return self::FAILURE;
            }

            $existing->role = User::ROLE_ADMIN;
            $existing->save();

            $this->info("«{$existing->name}» ahora es administrador.");

            return self::SUCCESS;
        }

        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        $validator = Validator::make(
            ['name' => $this->argument('name'), 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $this->argument('name'),
            'username' => User::makeUsername(explode('@', $email)[0]),
            'email' => $email,
            'password' => $password,
        ]);
        // El rol nunca se asigna en masa: solo aquí, de forma explícita.
        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->info("Administrador creado: {$user->email}");

        return self::SUCCESS;
    }
}
