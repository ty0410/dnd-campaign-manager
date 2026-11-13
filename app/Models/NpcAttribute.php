<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NpcAttribute extends Model
{
    protected $fillable = ['npc_id', 'label', 'value', 'is_visible', 'sort_order'];

    protected $casts = ['is_visible' => 'boolean'];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
