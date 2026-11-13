<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NpcNote extends Model
{
    protected $fillable = ['npc_id', 'user_id', 'body'];

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
