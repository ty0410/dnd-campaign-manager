<?php

namespace App\Services;

use App\Models\Character;

class CharacterSheetCalculator
{
    /**
     * Relaciona cada característica con su campo base y su bonificador.
     */
    private const ABILITIES = [
        'strength' => [
            'label' => 'Fuerza',
            'bonus' => 'strength_bonus',
        ],
        'dexterity' => [
            'label' => 'Destreza',
            'bonus' => 'dexterity_bonus',
        ],
        'constitution' => [
            'label' => 'Constitución',
            'bonus' => 'constitution_bonus',
        ],
        'intelligence' => [
            'label' => 'Inteligencia',
            'bonus' => 'intelligence_bonus',
        ],
        'wisdom' => [
            'label' => 'Sabiduría',
            'bonus' => 'wisdom_bonus',
        ],
        'charisma' => [
            'label' => 'Carisma',
            'bonus' => 'charisma_bonus',
        ],
    ];

    /**
     * Devuelve la puntuación efectiva de una característica.
     *
     * Si la puntuación base no está asignada, devuelve null.
     */
    public function effectiveScore(Character $character, string $ability): ?int
    {
        $this->validateAbility($ability);

        $baseScore = $character->getAttribute($ability);

        if ($baseScore === null) {
            return null;
        }

        $bonusField = self::ABILITIES[$ability]['bonus'];
        $customBonus = (int) ($character->getAttribute($bonusField) ?? 0);

        return (int) $baseScore + $customBonus;
    }

    /**
     * Calcula el modificador de una característica.
     *
     * Ejemplos:
     * 8  => -1
     * 10 =>  0
     * 17 => +3
     */
    public function abilityModifier(Character $character, string $ability): ?int
    {
        $score = $this->effectiveScore($character, $ability);

        if ($score === null) {
            return null;
        }

        return (int) floor(($score - 10) / 2);
    }

    /**
     * Devuelve las seis características con sus puntuaciones y modificadores.
     */
    public function abilityScores(Character $character): array
    {
        $result = [];

        foreach (self::ABILITIES as $ability => $definition) {
            $result[$ability] = [
                'name' => $definition['label'],
                'base_score' => $character->getAttribute($ability),
                'custom_bonus' => (int) (
                    $character->getAttribute($definition['bonus']) ?? 0
                ),
                'effective_score' => $this->effectiveScore(
                    $character,
                    $ability
                ),
                'modifier' => $this->abilityModifier(
                    $character,
                    $ability
                ),
            ];
        }

        return $result;
    }

    /**
     * Calcula el nivel total del personaje.
     *
     * Si ya tiene clases registradas, suma sus niveles.
     * Si todavía no tiene clases, utiliza el nivel antiguo como transición.
     */
    public function totalLevel(Character $character): int
    {
        $character->loadMissing('characterClasses');

        if ($character->characterClasses->isNotEmpty()) {
            return (int) $character->characterClasses->sum('level');
        }

        return (int) ($character->level ?? 1);
    }

    /**
     * Calcula el bonificador de competencia para el nivel total.
     */
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

    /**
     * Devuelve un resumen calculado de la ficha.
     */
    public function summary(Character $character): array
    {
        $totalLevel = $this->totalLevel($character);

        return [
            'total_level' => $totalLevel,
            'proficiency_bonus' => $this->proficiencyBonus($character),
            'ability_scores' => $this->abilityScores($character),
        ];
    }

    /**
     * Impide solicitar características que no estén admitidas.
     */
    private function validateAbility(string $ability): void
    {
        if (!array_key_exists($ability, self::ABILITIES)) {
            throw new \InvalidArgumentException(
                "Característica no válida: {$ability}"
            );
        }
    }
}
