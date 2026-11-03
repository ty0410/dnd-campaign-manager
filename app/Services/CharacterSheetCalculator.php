<?php

namespace App\Services;

use App\Models\Character;

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
                $ability, 'saving-throw-' . substr($ability, 0, 3), 'saving-throw-' . $ability,
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

    public function summary(Character $character): array
    {
        return [
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
