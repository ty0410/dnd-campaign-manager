<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
  protected $fillable = [
    'campaign_id',
    'user_id',
    'name',
    'race',
    'level',
    'proficiency_bonus',
    'description',
];

    /**
     * Campaña a la que pertenece el personaje.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Usuario que controla el personaje.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Clases del personaje.
     */
    public function characterClasses(): HasMany
    {
        return $this->hasMany(CharacterClass::class);
    }
}