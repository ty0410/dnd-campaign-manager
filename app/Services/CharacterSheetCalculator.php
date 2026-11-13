<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterInventory;

class CharacterSheetCalculator
{
    private const ABILITIES = [
        'strength' => ['label' => 'Fuerza', 'bonus' => 'strength_bonus'],
        'dexterity' => ['label' => 'Destreza', 'bonus' => 'dexterity_bonus'],
        'constitution' => ['label' => 'Constitución', 'bonus' => 'constitution_bonus'],
        'intelligence' => ['label' => 'Inteligencia', 'bonus' => 'intelligence_bonus'],
        'wisdom' => ['label' => 'Sabiduría', 'bonus' => 'wisdom_bonus'],
        'charisma' => ['label' => 'Carisma', 'bonus' => 'charisma_bonus'],
    ];

    private const SKILLS = [
        'acrobatics' => ['name' => 'Acrobacias', 'ability' => 'dexterity'],
        'animal-handling' => ['name' => 'Trato con animales', 'ability' => 'wisdom'],
        'arcana' => ['name' => 'Arcanos', 'ability' => 'intelligence'],
        'athletics' => ['name' => 'Atletismo', 'ability' => 'strength'],
        'deception' => ['name' => ' Engaño', 'ability' => 'charisma'],
        'history' => ['name' => 'Historia', 'ability' => 'intelligence'],
        'insight' => ['name' => 'Perspicacia', 'ability' => 'wisdom'],
        'intimidation' => ['name' => 'Intimidación', 'ability' => 'charisma'],
        'investigation' => ['name' => 'Investigación', 'ability' => 'intelligence'],
        'medicine' => ['name' => 'Medicina', 'ability' => 'wisdom'],
        'nature' => ['name' => 'Naturaleza', 'ability' => 'intelligence'],
        'perception' => ['name' => 'Percepción', 'ability' => 'wisdom'],
        'performance' => ['name' => 'Interpretación', 'ability' => 'charisma'],
        'persuasion' => ['name' => 'Persuasión', 'ability' => 'charisma'],
        'religion' => ['name' => 'Religión', 'ability' => 'intelligence'],
        'sleight-of-hand' => ['name' => 'Juego de manos', 'ability' => 'dexterity'],
        'stealth' => ['name' => 'Sigilo', 'ability' => 'dexterity'],
        'survival' => ['name' => 'Supervivencia', 'ability' => 'wisdom'],
    ];

    public function effectiveScore(Character $character, string $ability): ?int
    {
        $this->validateAbility($ability);
        $baseScore = $character->getAttribute($ability);
        if ($baseScore === null) return null;
        $bonus = self::ABILITIES[$ability]['bonus'];
        return (int) $baseScore + (int) ($character->getAttribute($bonus) ?? 0);
    }

    public function abilityModifier(Character $character, string $ability): ?int
    {
        $score = $this->effectiveScore($character, $ability);
        return $score === null ? null : (int) floor(($score - 10) / 2);
    }

    public function abilityScores(Character $character): array
    {
        $result = [];
        foreach (self::ABILITIES as $ability => $definition) {
            $result[$ability] = [
                'name' => $definition['label'],
                'base_score' => $character->getAttribute($ability),
                'custom_bonus' => (int) ($character->getAttribute($definition['bonus']) ?? 0),
                'effective_score' => $this->effectiveScore($character, $ability),
                'modifier' => $this->abilityModifier($character, $ability),
            ];
        }
        return $result;
    }

    public function totalLevel(Character $character): int
    {
        $character->loadMissing('characterClasses');
        return $character->characterClasses->isNotEmpty()
            ? (int) $character->characterClasses->sum('level')
            : (int) ($character->level ?? 1);
    }

    public function proficiencyBonus(Character $character): int
    {
        $level = max(1, min(20, $this->totalLevel($character)));
        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            default => 6,
        };
    }

    private function proficiencyLevel(Character $character, string $category, array $indexes): int
    {
        $override = $character->proficiencyOverrides
            ->first(fn ($item) => $item->category === $category && in_array($item->proficiency_index, $indexes, true));
        if ($override) return (int) $override->proficiency_level;

        $proficiency = $character->proficiencies
            ->first(fn ($item) => $item->is_active && $item->category === $category && in_array($item->proficiency_index, $indexes, true));
        return $proficiency ? (int) $proficiency->proficiency_level : 0;
    }

    public function skills(Character $character): array
    {
        $result = [];
        $bonus = $this->proficiencyBonus($character);
        foreach (self::SKILLS as $index => $skill) {
            $level = $this->proficiencyLevel($character, 'skill', [$index, 'skill-' . $index]);
            $modifier = $this->abilityModifier($character, $skill['ability']);
            $result[$index] = [
                'index' => $index,
                'name' => trim($skill['name']),
                'ability' => self::ABILITIES[$skill['ability']]['label'],
                'ability_modifier' => $modifier,
                'proficiency_level' => $level,
                'proficiency_label' => $level >= 2 ? 'Pericia' : ($level === 1 ? 'Competente' : 'Sin competencia'),
                'modifier' => $modifier === null ? null : $modifier + ($bonus * $level),
            ];
        }
        return $result;
    }

    public function savingThrows(Character $character): array
    {
        $result = [];
        $bonus = $this->proficiencyBonus($character);
        foreach (self::ABILITIES as $ability => $definition) {
            $level = $this->proficiencyLevel($character, 'saving_throw', [
                $ability, substr($ability, 0, 3), 'saving-throw-' . substr($ability, 0, 3), 'saving-throw-' . $ability,
            ]);
            $modifier = $this->abilityModifier($character, $ability);
            $result[$ability] = [
                'name' => $definition['label'],
                'proficiency_level' => $level,
                'modifier' => $modifier === null ? null : $modifier + ($bonus * $level),
            ];
        }
        return $result;
    }

    /**
     * Clase de armadura según las reglas de D&D 5.ª edición:
     *  - Con armadura: base de la armadura + Destreza (limitada en armaduras medias, sin Destreza en pesadas).
     *  - Sin armadura: 10 + Destreza. El bárbaro puede usar 10 + Des + Con y el monje 10 + Des + Sab.
     *  - Escudo equipado: +2 (o lo que indique el objeto).
     *  - Más el bonificador manual de la ficha.
     */
    public function armorClass(Character $character): array
    {
        $dex = $this->abilityModifier($character, 'dexterity') ?? 0;
        $items = $character->inventory->where('is_equipped', true);

        $hydrate = function ($item) {
            if ($item->equipment_data === null && $item->equipment_index) {
                try {
                    $detail = app(DndApiService::class)->getEquipmentItem($item->equipment_index);
                    if ($detail) {
                        $item->equipment_data = CharacterInventory::dataFromCatalog($detail);
                        $item->save();
                    }
                } catch (\Throwable $e) {
                    // Sin conexión con el catálogo: se ignora este objeto por ahora.
                }
            }

            return $item;
        };

        $armorOf = fn ($item) => $item->equipment_data['armor_category'] ?? null;
        $items = $items->map($hydrate);

        $body = $items->first(fn ($item) => in_array($armorOf($item), ['Light', 'Medium', 'Heavy'], true));
        $shield = $items->first(fn ($item) => $armorOf($item) === 'Shield');

        $classIndexes = $character->characterClasses->pluck('class_index')->all();

        if ($body) {
            $data = $body->equipment_data['armor_class'] ?? [];
            $dexPart = ! empty($data['dex_bonus'])
                ? (isset($data['max_bonus']) && $data['max_bonus'] !== null ? min($dex, (int) $data['max_bonus']) : $dex)
                : 0;
            $value = (int) ($data['base'] ?? 10) + $dexPart;
            $label = $body->item_name;
        } else {
            $value = 10 + $dex;
            $label = 'Base 10';

            // Defensa sin armadura (no se aplica a la armadura equipada).
            if (in_array('barbarian', $classIndexes, true)) {
                $alt = 10 + $dex + ($this->abilityModifier($character, 'constitution') ?? 0);
                if ($alt > $value) { $value = $alt; $label = 'Defensa sin armadura'; }
            }
            if (in_array('monk', $classIndexes, true) && ! $shield) {
                $alt = 10 + $dex + ($this->abilityModifier($character, 'wisdom') ?? 0);
                if ($alt > $value) { $value = $alt; $label = 'Defensa sin armadura'; }
            }
        }

        if ($shield) {
            $value += (int) ($shield->equipment_data['armor_class']['base'] ?? 2);
        }

        $bonus = (int) $character->ac_bonus;

        return [
            'value' => $value + $bonus,
            'base_label' => $label,
            'shield' => (bool) $shield,
            'bonus' => $bonus,
        ];
    }

    public function summary(Character $character): array
    {
        return [
            'armor_class' => $this->armorClass($character),
            'total_level' => $this->totalLevel($character),
            'proficiency_bonus' => $this->proficiencyBonus($character),
            'ability_scores' => $this->abilityScores($character),
            'skills' => $this->skills($character),
            'saving_throws' => $this->savingThrows($character),
        ];
    }

    private function validateAbility(string $ability): void
    {
        if (!array_key_exists($ability, self::ABILITIES)) {
            throw new \InvalidArgumentException("Característica no válida: {$ability}");
        }
    }
}
