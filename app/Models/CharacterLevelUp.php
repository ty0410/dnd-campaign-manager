<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterLevelUp extends Model
{
    protected $fillable = [
        'character_id',
        'character_class_id',
        'class_level',
        'hit_die',
        'hp_method',
        'base_hp_gained',
        'constitution_modifier_applied',
        'total_hp_gained',
    ];

    protected $casts = [
        'class_level' => 'integer',
        'hit_die' => 'integer',
        'base_hp_gained' => 'integer',
        'constitution_modifier_applied' => 'integer',
        'total_hp_gained' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function characterClass(): BelongsTo
    {
        return $this->belongsTo(CharacterClass::class);
    }
}
