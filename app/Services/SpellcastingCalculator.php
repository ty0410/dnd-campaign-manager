<?php

namespace App\Services;

use App\Models\Character;

/** Calcula los espacios de conjuro multiclase según las reglas 2014. */
class SpellcastingCalculator
{
    private const FULL = ['bard', 'cleric', 'druid', 'sorcerer', 'wizard'];
    private const HALF = ['paladin', 'ranger'];
    private const SLOTS = [
        0 => [0,0,0,0,0,0,0,0,0],
        1 => [2,0,0,0,0,0,0,0,0], 2 => [3,0,0,0,0,0,0,0,0],
        3 => [4,2,0,0,0,0,0,0,0], 4 => [4,3,0,0,0,0,0,0,0],
        5 => [4,3,2,0,0,0,0,0,0], 6 => [4,3,3,0,0,0,0,0,0],
        7 => [4,3,3,1,0,0,0,0,0], 8 => [4,3,3,2,0,0,0,0,0],
        9 => [4,3,3,3,1,0,0,0,0], 10 => [4,3,3,3,2,0,0,0,0],
        11 => [4,3,3,3,2,1,0,0,0], 12 => [4,3,3,3,2,1,0,0,0],
        13 => [4,3,3,3,2,1,1,0,0], 14 => [4,3,3,3,2,1,1,0,0],
        15 => [4,3,3,3,2,1,1,1,0], 16 => [4,3,3,3,2,1,1,1,0],
        17 => [4,3,3,3,2,1,1,1,1], 18 => [4,3,3,3,3,1,1,1,1],
        19 => [4,3,3,3,3,2,1,1,1], 20 => [4,3,3,3,3,2,2,1,1],
    ];

    public function summary(Character $character): array
    {
        $casterLevel = 0;
        $pact = null;
        foreach ($character->characterClasses as $class) {
            $index = $class->class_index;
            if (in_array($index, self::FULL, true)) $casterLevel += $class->level;
            elseif (in_array($index, self::HALF, true)) $casterLevel += intdiv($class->level, 2);
            elseif ($index === 'fighter' && in_array($class->subclass_index, ['eldritch-knight'], true)) $casterLevel += intdiv($class->level, 3);
            elseif ($index === 'rogue' && in_array($class->subclass_index, ['arcane-trickster'], true)) $casterLevel += intdiv($class->level, 3);
            if ($index === 'warlock') $pact = $this->pactSlots($class->level);
        }
        $casterLevel = min(20, $casterLevel);
        $row = self::SLOTS[$casterLevel] ?? self::SLOTS[0];
        $normal = [];
        foreach ($row as $i => $count) if ($count > 0) $normal[] = ['slot_type' => 'normal', 'spell_level' => $i + 1, 'total_slots' => $count];
        return [
            'multiclass_caster_level' => $casterLevel,
            'normal_slots' => $normal,
            'pact_slots' => $pact,
            'note' => 'Los espacios se calculan con la tabla de lanzador multiclase de D&D 5e 2014. Los espacios de Pacto del brujo se mantienen separados.',
        ];
    }

    private function pactSlots(int $level): ?array
    {
        if ($level < 1) return null;
        return [
            'slot_level' => match (true) { $level >= 17 => 5, $level >= 9 => 5, $level >= 7 => 4, $level >= 5 => 3, $level >= 3 => 2, default => 1 },
            'total_slots' => match (true) { $level >= 17 => 4, $level >= 11 => 3, $level >= 2 => 2, default => 1 },
            'class_level' => $level,
        ];
    }
}
