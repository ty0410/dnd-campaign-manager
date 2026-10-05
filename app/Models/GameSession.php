<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $fillable = [
        'campaign_id',
        'title',
        'session_date',
        'summary',
        'notes',
    ];

    /**
     * Campaña a la que pertenece la sesión.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Encuentros realizados durante esta sesión.
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }
}