<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_private',
    ];

    /**
     * Campaña a la que pertenece la nota.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Usuario que creó la nota.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}