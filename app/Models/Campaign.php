<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'master_id',
    ];

    /**
     * Usuario que es el Dungeon Master.
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    /**
     * Usuarios que participan en la campaña.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    /**
     * Personajes de la campaña.
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * NPCs de la campaña.
     */
    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    /**
     * Localizaciones de la campaña.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Sesiones de juego de la campaña.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Encuentros de la campaña.
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /**
     * Notas de la campaña.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}