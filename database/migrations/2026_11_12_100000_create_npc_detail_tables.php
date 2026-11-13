<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('npcs', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('location');
        });

        // Características personalizadas del PNJ (como un bloque de criatura).
        Schema::create('npc_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->string('label', 100);
            $table->text('value');
            // Si es false, solo lo ve el Dungeon Master.
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Apuntes de cada jugador sobre un PNJ (uno por jugador).
        Schema::create('npc_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->unique(['npc_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npc_notes');
        Schema::dropIfExists('npc_attributes');

        Schema::table('npcs', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
