<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterInventory extends Model
{
    protected $table = 'character_inventory';

    protected $fillable = [
        'character_id',
        'equipment_index',
        'equipment_data',
        'item_name',
        'quantity',
        'weight',
        'is_equipped',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'equipment_data' => 'array',
        'weight' => 'decimal:2',
        'is_equipped' => 'boolean',
    ];

    /**
     * Resume un objeto del catálogo con lo que necesita la ficha:
     * datos de armadura y si suele poseerse en cantidad (munición, pociones...).
     */
    public static function dataFromCatalog(array $item): array
    {
        $name = $item['name'] ?? '';
        $stackable = ($item['equipment_category']['index'] ?? '') === 'ammunition'
            || in_array($item['gear_category']['index'] ?? '', ['ammunition', 'potion'], true)
            || (bool) preg_match('/(arrow|bolt|bullet|dart|needle|potion|vial|flask|ration|torch|oil|candle|chalk|ink|parchment|paper|piton|caltrops|ball bearings|antitoxin|holy water|acid|alchemist)/i', $name);

        return [
            'category' => $item['equipment_category']['name'] ?? null,
            'armor_category' => $item['armor_category'] ?? null,
            'armor_class' => $item['armor_class'] ?? null,
            'stackable' => $stackable,
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
