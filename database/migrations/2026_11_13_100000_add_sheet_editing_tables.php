<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos del objeto del catálogo necesarios para la ficha (armadura, apilable...).
        Schema::table('character_inventory', function (Blueprint $table) {
            $table->json('equipment_data')->nullable()->after('equipment_index');
        });

        // Bonificador manual a la clase de armadura (anillos, conjuros, estilos de combate...).
        Schema::table('characters', function (Blueprint $table) {
            $table->smallInteger('ac_bonus')->default(0);
        });

        // Ataques y conjuros de daño registrados por el jugador.
        Schema::create('character_attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();

            $table->string('name', 120);
            // attack: tirada de ataque (d20 + bonificador). save: el objetivo hace una salvación contra tu CD.
            $table->string('kind', 10)->default('attack');
            // Característica usada para el bonificador de ataque o la CD (en conjuros, la de lanzamiento).
            $table->string('ability', 20)->default('strength');
            $table->boolean('add_proficiency')->default(true);
            // Salvación que debe superar el objetivo (solo kind = save).
            $table->string('save_ability', 20)->nullable();
            $table->smallInteger('bonus')->default(0);

            $table->string('damage_dice', 10)->nullable();
            $table->smallInteger('damage_bonus')->default(0);
            $table->boolean('damage_add_ability')->default(false);
            $table->string('damage_type', 60)->nullable();
            $table->string('notes', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_attacks');

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('ac_bonus');
        });

        Schema::table('character_inventory', function (Blueprint $table) {
            $table->dropColumn('equipment_data');
        });
    }
};
