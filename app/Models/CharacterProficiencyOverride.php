<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterProficiencyOverride extends Model
{
    protected $fillable = [
        'character_id',
        'category',
        'proficiency_index',
        'proficiency_name',
        'proficiency_level',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
