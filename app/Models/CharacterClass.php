<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterClass extends Model
{
    protected $fillable = [
        'character_id',
        'class',
        'level',
        'class_index',
        'is_primary',
        'hit_die',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_primary' => 'boolean',
        'hit_die' => 'integer',
    ];

    /**
     * Personaje al que pertenece esta clase.
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Subidas de nivel registradas para esta clase.
     */
    public function levelUps(): HasMany
    {
        return $this->hasMany(CharacterLevelUp::class);
    }
}
