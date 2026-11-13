<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_character_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();

            // Quién inició la solicitud (el jugador o el Dungeon Master).
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            // player_request: el jugador pide entrar; lo aprueba el DM.
            // dm_invitation: el DM invita al personaje; lo aprueba su dueño.
            $table->string('direction', 20);
            $table->string('status', 20)->default('pending');

            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['character_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_character_requests');
    }
};
