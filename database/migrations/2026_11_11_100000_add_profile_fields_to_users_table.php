<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nombre de usuario público (único) y foto de perfil.
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('avatar_path')->nullable()->after('email');

            // Baja de la cuenta: se conserva el contenido pero no se puede iniciar sesión.
            $table->timestamp('deactivated_at')->nullable()->after('role');
        });

        // Las cuentas existentes reciben un nombre de usuario derivado de su nombre.
        $taken = [];

        DB::table('users')->orderBy('id')->each(function ($user) use (&$taken) {
            $base = Str::of(Str::ascii($user->name))
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->limit(22, '')
                ->toString() ?: 'usuario';

            $username = $base;

            if (isset($taken[$username])) {
                $username = $base . '_' . $user->id;
            }

            $taken[$username] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'avatar_path', 'deactivated_at']);
        });
    }
};
