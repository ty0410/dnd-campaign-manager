<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterClass extends Model
{
    protected $fillable = [
        'character_id',
        'class',
        'level',
    ];

    /**
     * Personaje al que pertenece esta clase.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}