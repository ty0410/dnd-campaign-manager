<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reparto de niveles elegido al crear: [{class_index, level}], la clase principal primero.
        Schema::table('characters', function (Blueprint $table) {
            $table->json('level_plan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('level_plan');
        });
    }
};
