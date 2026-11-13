<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterAttack extends Model
{
    protected $fillable = [
        'character_id', 'name', 'kind', 'ability', 'add_proficiency', 'save_ability',
        'bonus', 'damage_dice', 'damage_bonus', 'damage_add_ability', 'damage_type',
        'notes', 'sort_order',
    ];

    protected $casts = [
        'add_proficiency' => 'boolean',
        'damage_add_ability' => 'boolean',
        'bonus' => 'integer',
        'damage_bonus' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
