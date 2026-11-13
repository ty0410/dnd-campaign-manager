<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    protected $fillable = [
        'name',
        'description',
        'master_id',
        'image_path',
        'invite_code',
    ];

    /**
     * El código de invitación solo lo ve el Dungeon Master
     * (el controlador lo hace visible cuando corresponde).
     */
    protected $hidden = ['invite_code'];

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (! $campaign->invite_code) {
                $campaign->invite_code = static::generateInviteCode();
            }
        });
    }

    /**
     * Código corto sin caracteres ambiguos (sin 0, O, 1, I).
     */
    public static function generateInviteCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

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
public function characters(): BelongsToMany
{
    return $this->belongsToMany(Character::class)
        ->withTimestamps();
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