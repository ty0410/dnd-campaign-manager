<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterSpellSlot extends Model
{
    protected $table = 'character_spell_slots';

    protected $fillable = [
        'character_id',
        'slot_type',
        'spell_level',
        'total_slots',
        'used_slots',
    ];

    protected $casts = [
        'spell_level' => 'integer',
        'total_slots' => 'integer',
        'used_slots' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
