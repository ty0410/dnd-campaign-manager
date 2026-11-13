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
        'image_path',
    ];

    protected $appends = ['image_url'];

    protected function imageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->image_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path)
                : null
        );
    }

    public function customAttributes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NpcAttribute::class)->orderBy('sort_order')->orderBy('id');
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NpcNote::class);
    }

    /**
     * Campaña a la que pertenece el NPC.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}