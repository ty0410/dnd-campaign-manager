<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Npc extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'race',
        'role',
        'description',
        'location',
    ];

    /**
     * Campaña a la que pertenece el NPC.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}