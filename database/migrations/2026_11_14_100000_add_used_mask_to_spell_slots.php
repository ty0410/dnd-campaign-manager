<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada bit indica qué círculo de un nivel está gastado, para poder tachar cualquiera.
        Schema::table('character_spell_slots', function (Blueprint $table) {
            $table->unsignedInteger('used_mask')->default(0)->after('used_slots');
        });

        // Las filas existentes conservan sus espacios gastados (los primeros círculos).
        DB::table('character_spell_slots')->where('used_slots', '>', 0)->orderBy('id')->each(function ($slot) {
            DB::table('character_spell_slots')
                ->where('id', $slot->id)
                ->update(['used_mask' => (1 << (int) $slot->used_slots) - 1]);
        });
    }

    public function down(): void
    {
        Schema::table('character_spell_slots', function (Blueprint $table) {
            $table->dropColumn('used_mask');
        });
    }
};
