<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nivel al que el jugador quiere llegar al crear el personaje. Se crea en nivel 1 y
        // sube paso a paso (PG, subclase, mejoras y dotes); al alcanzarlo se borra.
        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedTinyInteger('target_level')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('target_level');
        });
    }
};
