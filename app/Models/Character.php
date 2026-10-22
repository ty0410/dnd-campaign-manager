<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    protected $fillable = [
        // Datos existentes
        'campaign_id',
        'user_id',
        'name',
        'race',
        'level',
        'proficiency_bonus',
        'description',
        'image_path',


        // Raza y trasfondo
        'race_index',
        'subrace_index',
        'background_index',
        'background',

        // Características base
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',

        // Bonificadores personalizados
        'strength_bonus',
        'dexterity_bonus',
        'constitution_bonus',
        'intelligence_bonus',
        'wisdom_bonus',
        'charisma_bonus',

        // Método de asignación de características
        'ability_score_method',

        // Puntos de golpe
        'current_hit_points',
        'maximum_hit_points',
        'temporary_hit_points',

        // Experiencia e inspiración
        'experience_points',
        'inspiration',

        // Información narrativa
        'personality_trait',
        'ideal',
        'bond',
        'flaw',
        'notes',
    ];

    protected $casts = [
        'strength' => 'integer',
        'dexterity' => 'integer',
        'constitution' => 'integer',
        'intelligence' => 'integer',
        'wisdom' => 'integer',
        'charisma' => 'integer',

        'strength_bonus' => 'integer',
        'dexterity_bonus' => 'integer',
        'constitution_bonus' => 'integer',
        'intelligence_bonus' => 'integer',
        'wisdom_bonus' => 'integer',
        'charisma_bonus' => 'integer',

        'current_hit_points' => 'integer',
        'maximum_hit_points' => 'integer',
        'temporary_hit_points' => 'integer',
        'experience_points' => 'integer',
        'inspiration' => 'boolean',
    ];

    /**
     * Campaña a la que pertenece el personaje.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
    public function campaigns(): BelongsToMany
{
    return $this->belongsToMany(Campaign::class)
        ->withTimestamps();
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

    /**
     * Historial de subidas de nivel.
     */
    public function levelUps(): HasMany
    {
        return $this->hasMany(CharacterLevelUp::class);
    }

    /**
     * Competencias procedentes de distintas fuentes.
     */
    public function proficiencies(): HasMany
    {
        return $this->hasMany(CharacterProficiency::class);
    }

    /**
     * Cambios manuales de competencias.
     */
    public function proficiencyOverrides(): HasMany
    {
        return $this->hasMany(CharacterProficiencyOverride::class);
    }

    /**
     * Objetos del inventario.
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(CharacterInventory::class);
    }

    /**
     * Monedas del personaje.
     */
    public function currency(): HasOne
    {
        return $this->hasOne(CharacterCurrency::class);
    }

    /**
     * Conjuros del personaje.
     */
    public function spells(): HasMany
    {
        return $this->hasMany(CharacterSpell::class);
    }

    /**
     * Espacios de conjuro disponibles.
     */
    public function spellSlots(): HasMany
    {
        return $this->hasMany(CharacterSpellSlot::class);
    }
    /**
 * Devuelve la URL pública del retrato del personaje.
 */
protected function imageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
{
    return \Illuminate\Database\Eloquent\Casts\Attribute::get(
        fn () => $this->image_path
            ? \Illuminate\Support\Facades\Storage::disk('public')
                ->url($this->image_path)
            : null
    );
}

/**
 * Incluye image_url al serializar el personaje como JSON.
 */
protected $appends = ['image_url'];
}
