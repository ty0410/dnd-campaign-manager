<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterCurrency extends Model
{
    protected $table = 'character_currency';

    protected $fillable = [
        'character_id',
        'copper',
        'silver',
        'electrum',
        'gold',
        'platinum',
    ];

    protected $casts = [
        'copper' => 'integer',
        'silver' => 'integer',
        'electrum' => 'integer',
        'gold' => 'integer',
        'platinum' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
