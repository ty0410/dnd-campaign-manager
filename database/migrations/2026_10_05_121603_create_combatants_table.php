<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('combatants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('encounter_id')
                ->constrained('encounters')
                ->cascadeOnDelete();

            $table->foreignId('character_id')
                ->nullable()
                ->constrained('characters')
                ->cascadeOnDelete();

            $table->string('monster_index')
                ->nullable();

            $table->string('name');

            $table->unsignedSmallInteger('initiative')
                ->nullable();

            $table->unsignedSmallInteger('hit_points')
                ->nullable();

            $table->unsignedSmallInteger('max_hit_points')
                ->nullable();

            $table->boolean('is_player')
                ->default(false);

            $table->boolean('is_defeated')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combatants');
    }
};