<?php

namespace Tests\Unit;

use App\Models\Character;
use App\Models\CharacterClass;
use App\Services\SpellcastingCalculator;
use PHPUnit\Framework\TestCase;

class SpellcastingCalculatorTest extends TestCase
{
    public function test_full_and_half_caster_levels_are_combined_for_multiclass_slots(): void
    {
        $character = new Character();
        $character->setRelation('characterClasses', collect([
            new CharacterClass(['class_index' => 'wizard', 'level' => 5]),
            new CharacterClass(['class_index' => 'paladin', 'level' => 4]),
        ]));

        $summary = (new SpellcastingCalculator())->summary($character);

        $this->assertSame(7, $summary['multiclass_caster_level']);
        $this->assertSame([4, 3, 3, 1], array_column($summary['normal_slots'], 'total_slots'));
    }

    public function test_warlock_pact_slots_are_kept_separate_from_multiclass_slots(): void
    {
        $character = new Character();
        $character->setRelation('characterClasses', collect([
            new CharacterClass(['class_index' => 'wizard', 'level' => 5]),
            new CharacterClass(['class_index' => 'warlock', 'level' => 3]),
        ]));

        $summary = (new SpellcastingCalculator())->summary($character);

        $this->assertSame(5, $summary['multiclass_caster_level']);
        $this->assertSame(2, $summary['pact_slots']['total_slots']);
        $this->assertSame(2, $summary['pact_slots']['slot_level']);
    }

    public function test_eldritch_knight_contributes_one_third_of_fighter_levels(): void
    {
        $character = new Character();
        $character->setRelation('characterClasses', collect([
            new CharacterClass(['class_index' => 'fighter', 'subclass_index' => 'eldritch-knight', 'level' => 6]),
            new CharacterClass(['class_index' => 'wizard', 'level' => 2]),
        ]));

        $summary = (new SpellcastingCalculator())->summary($character);

        $this->assertSame(4, $summary['multiclass_caster_level']);
        $this->assertSame([4, 3], array_column($summary['normal_slots'], 'total_slots'));
    }
}
