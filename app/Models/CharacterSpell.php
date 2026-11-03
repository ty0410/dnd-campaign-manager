<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterSpell extends Model
{
    protected $fillable = [
        'character_id',
        'spell_index',
        'spell_name',
        'spell_level',
        'spell_type',
        'source_class_index',
        'is_known',
        'is_prepared',
        'is_exception',
        'notes',
    ];

    protected $casts = [
        'spell_level' => 'integer',
        'is_known' => 'boolean',
        'is_prepared' => 'boolean',
        'is_exception' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
