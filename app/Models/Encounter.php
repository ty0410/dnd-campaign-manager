<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Encounter extends Model
{
    protected $fillable = [
        'campaign_id',
        'game_session_id',
        'name',
        'description',
        'type',
        'status',
    ];

    /**
     * Campaña a la que pertenece el encuentro.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Sesión de juego a la que pertenece el encuentro.
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * Participantes del encuentro.
     */
    public function combatants(): HasMany
    {
        return $this->hasMany(Combatant::class);
    }
}