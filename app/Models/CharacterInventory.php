<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterInventory extends Model
{
    protected $table = 'character_inventory';

    protected $fillable = [
        'character_id',
        'equipment_index',
        'item_name',
        'quantity',
        'weight',
        'is_equipped',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'is_equipped' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
