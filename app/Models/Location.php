<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'campaign_id',
        'parent_id',
        'name',
        'type',
        'description',
    ];

    /**
     * Campaña a la que pertenece la localización.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Localización padre.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Localizaciones hijas.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }
}