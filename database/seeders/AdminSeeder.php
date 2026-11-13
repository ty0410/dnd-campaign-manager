<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea un administrador a partir del .env:
 *   ADMIN_NAME, ADMIN_EMAIL y ADMIN_PASSWORD.
 * Uso: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Define ADMIN_EMAIL y ADMIN_PASSWORD en el .env para crear el administrador.');

            return;
        }

        $user = User::firstOrNew(['email' => mb_strtolower($email)]);

        if (! $user->exists) {
            $user->name = env('ADMIN_NAME', 'Administrador');
            $user->username = User::makeUsername(explode('@', $email)[0]);
            $user->password = $password;
        }

        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->command?->info("Administrador listo: {$user->email}");
    }
}
