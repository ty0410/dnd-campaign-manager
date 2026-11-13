<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Nombre de usuario libre derivado de un texto (nombre, parte local del correo...).
     */
    public static function makeUsername(string $base): string
    {
        $slug = \Illuminate\Support\Str::of(\Illuminate\Support\Str::ascii($base))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->limit(22, '')
            ->toString() ?: 'usuario';

        $candidate = $slug;
        $n = 1;

        while (static::where('username', $candidate)->exists()) {
            $candidate = $slug . '_' . (++$n);
        }

        return $candidate;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * URL pública de la foto de perfil (null si no tiene).
     */
    protected function avatarUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn () => $this->avatar_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path)
                : null
        );
    }

    protected $appends = ['avatar_url'];

    /**
     * Campañas donde el usuario es Dungeon Master.
     */
    public function masteredCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'master_id');
    }

    /**
     * Campañas en las que participa el usuario.
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withTimestamps();
    }

    /**
     * Personajes controlados por el usuario.
     */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    /**
     * Notas creadas por el usuario.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}