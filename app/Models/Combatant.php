<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Combatant extends Model
{
    protected $fillable = [
        'encounter_id',
        'character_id',
        'monster_index',
        'name',
        'initiative',
        'hit_points',
        'max_hit_points',
        'is_player',
        'is_defeated',
    ];

    /**
     * Encuentro al que pertenece el combatiente.
     */
    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    /**
     * Personaje asociado al combatiente.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}