<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterProficiency extends Model
{
    protected $fillable = [
        'character_id',
        'category',
        'proficiency_index',
        'proficiency_name',
        'source_type',
        'source_index',
        'proficiency_level',
        'is_active',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
