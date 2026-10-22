
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade los campos de la ficha de personaje.
     */
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Raza y trasfondo
            $table->string('race_index')->nullable();
            $table->string('subrace_index')->nullable();
            $table->string('background_index')->nullable();
            $table->string('background')->nullable();

            // Puntuaciones base de las seis características
            $table->unsignedTinyInteger('strength')->nullable();
            $table->unsignedTinyInteger('dexterity')->nullable();
            $table->unsignedTinyInteger('constitution')->nullable();
            $table->unsignedTinyInteger('intelligence')->nullable();
            $table->unsignedTinyInteger('wisdom')->nullable();
            $table->unsignedTinyInteger('charisma')->nullable();

            // Bonificadores personalizados
            $table->smallInteger('strength_bonus')->default(0);
            $table->smallInteger('dexterity_bonus')->default(0);
            $table->smallInteger('constitution_bonus')->default(0);
            $table->smallInteger('intelligence_bonus')->default(0);
            $table->smallInteger('wisdom_bonus')->default(0);
            $table->smallInteger('charisma_bonus')->default(0);

            // Método de asignación de características
            $table->string('ability_score_method')->nullable();

            // Puntos de golpe
            $table->unsignedSmallInteger('current_hit_points')->nullable();
            $table->unsignedSmallInteger('maximum_hit_points')->nullable();
            $table->unsignedSmallInteger('temporary_hit_points')->default(0);

            // Experiencia e inspiración
            $table->unsignedInteger('experience_points')->default(0);
            $table->boolean('inspiration')->default(false);

            // Información narrativa
            $table->text('personality_trait')->nullable();
            $table->text('ideal')->nullable();
            $table->text('bond')->nullable();
            $table->text('flaw')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Revierte únicamente los campos añadidos por esta migración.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'race_index',
                'subrace_index',
                'background_index',
                'background',
                'strength',
                'dexterity',
                'constitution',
                'intelligence',
                'wisdom',
                'charisma',
                'strength_bonus',
                'dexterity_bonus',
                'constitution_bonus',
                'intelligence_bonus',
                'wisdom_bonus',
                'charisma_bonus',
                'ability_score_method',
                'current_hit_points',
                'maximum_hit_points',
                'temporary_hit_points',
                'experience_points',
                'inspiration',
                'personality_trait',
                'ideal',
                'bond',
                'flaw',
                'notes',
            ]);
        });
    }
};
