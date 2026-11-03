<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterClass;
use App\Services\DndApiService;
use App\Services\SpellcastingCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CharacterMagicController extends Controller
{
    public function subclassOptions(Request $request, int $characterId, int $classId, DndApiService $api)
    {
        $character = $this->accessible($request, $characterId);
        $class = $character?->characterClasses()->find($classId);
        if (!$class) return response()->json(['message' => 'Personaje o clase no encontrados.'], 404);
        $details = $api->getClass($class->class_index);
        return response()->json([
            'class' => $class,
            'subclasses' => collect(data_get($details, 'subclasses', []))->map(fn ($s) => ['index' => $s['index'] ?? null, 'name' => $s['name'] ?? $s['index']])->values(),
            'subclass_level' => $this->subclassLevel($class->class_index),
            'selected' => $class->subclass_index,
        ]);
    }

    public function chooseSubclass(Request $request, int $characterId, int $classId, DndApiService $api)
    {
        $character = $this->accessible($request, $characterId);
        $class = $character?->characterClasses()->find($classId);
        if (!$class) return response()->json(['message' => 'Personaje o clase no encontrados.'], 404);
        $data = $request->validate(['subclass_index' => ['required', 'string', 'max:100']]);
        if ($class->level < $this->subclassLevel($class->class_index)) throw ValidationException::withMessages(['subclass_index' => ['Todavía no has alcanzado el nivel necesario para elegir subclase.']]);
        $details = $api->getClass($class->class_index);
        $valid = collect(data_get($details, 'subclasses', []))->contains(fn ($s) => ($s['index'] ?? null) === $data['subclass_index']);
        if (!$valid) throw ValidationException::withMessages(['subclass_index' => ['La subclase no pertenece a esta clase.']]);
        $subclass = $api->getSubclass($data['subclass_index']);
        if (!$subclass) throw ValidationException::withMessages(['subclass_index' => ['No se ha podido consultar la subclase.']]);
        $class->update(['subclass_index' => $subclass['index'], 'subclass_name' => $subclass['name']]);
        return response()->json(['message' => 'Subclase guardada.', 'character_class' => $class->fresh()]);
    }

    public function spellOptions(Request $request, int $characterId, int $classId, DndApiService $api)
    {
        $character = $this->accessible($request, $characterId);
        $class = $character?->characterClasses()->find($classId);
        if (!$class) return response()->json(['message' => 'Personaje o clase no encontrados.'], 404);
        $details = $api->getClass($class->class_index);
        $subclassCaster = in_array($class->subclass_index, ['eldritch-knight', 'arcane-trickster'], true);
        if (!$details || (empty($details['spellcasting']) && !$subclassCaster)) return response()->json(['spellcasting' => false, 'spells' => []]);
        $progress = $subclassCaster
            ? $api->getSubclassLevel($class->subclass_index, $class->level)
            : $api->getClassLevel($class->class_index, $class->level);
        $maxLevel = 0;
        foreach (range(1, 9) as $level) if ((int) data_get($progress, "spellcasting.spell_slots_level_{$level}", 0) > 0) $maxLevel = $level;
        $list = $subclassCaster ? $api->getSubclassSpells($class->subclass_index) : $api->getClassSpells($class->class_index);
        $results = collect($list['results'] ?? [])->take(250);
        $detailsByIndex = collect($api->getSpellDetailsForIndexes($results->pluck('index')->all()))->keyBy('index');
        $spells = $results->map(function ($spell) use ($detailsByIndex, $maxLevel) {
            $detail = $detailsByIndex->get($spell['index']);
            if (!$detail || (int) ($detail['level'] ?? 0) > $maxLevel) return null;
            return ['index' => $detail['index'], 'name' => $detail['name'], 'level' => (int) $detail['level'], 'school' => data_get($detail, 'school.name'), 'url' => data_get($spell, 'url')];
        })->filter()->values();
        return response()->json([
            'spellcasting' => true,
            'class' => ['index' => $class->class_index, 'name' => $class->class, 'level' => $class->level],
            'spellcasting_ability' => $subclassCaster ? 'int' : data_get($details, 'spellcasting.spellcasting_ability.index'),
            'spell_type' => in_array($class->class_index, ['bard', 'ranger', 'sorcerer', 'warlock', 'fighter', 'rogue'], true) ? 'known' : 'prepared',
            'max_spell_level' => $maxLevel,
            'cantrips_known' => data_get($progress, 'spellcasting.cantrips_known'),
            'spells_known' => data_get($progress, 'spellcasting.spells_known'),
            'spells' => $spells,
            'selected' => $character->spells()->where('source_class_index', $class->class_index)->get(),
        ]);
    }

    public function saveSpells(Request $request, int $characterId, int $classId, DndApiService $api)
    {
        $character = $this->accessible($request, $characterId);
        $class = $character?->characterClasses()->find($classId);
        if (!$class) return response()->json(['message' => 'Personaje o clase no encontrados.'], 404);
        $data = $request->validate([
            'spells' => ['present', 'array', 'max:100'],
            'spells.*.index' => ['required', 'string', 'max:100'],
            'spells.*.prepared' => ['sometimes', 'boolean'],
        ]);
        $details = $api->getClass($class->class_index);
        $subclassCaster = in_array($class->subclass_index, ['eldritch-knight', 'arcane-trickster'], true);
        if (!$details || (empty($details['spellcasting']) && !$subclassCaster)) throw ValidationException::withMessages(['spells' => ['Esta clase no tiene lanzamiento de conjuros.']]);
        $progress = $subclassCaster
            ? $api->getSubclassLevel($class->subclass_index, $class->level)
            : $api->getClassLevel($class->class_index, $class->level);
        $maxLevel = 0;
        foreach (range(1, 9) as $level) if ((int) data_get($progress, "spellcasting.spell_slots_level_{$level}", 0) > 0) $maxLevel = $level;
        $spellList = $subclassCaster ? $api->getSubclassSpells($class->subclass_index) : $api->getClassSpells($class->class_index);
        $available = collect($spellList['results'] ?? [])->pluck('index')->all();
        $chosen = collect($data['spells'])->unique('index');
        if ($chosen->count() !== count($data['spells'])) throw ValidationException::withMessages(['spells' => ['No repitas el mismo conjuro.']]);
        $known = $subclassCaster || in_array($class->class_index, ['bard', 'ranger', 'sorcerer', 'warlock'], true);
        $spellRows = [];
        foreach ($chosen as $entry) {
            if (!in_array($entry['index'], $available, true)) throw ValidationException::withMessages(['spells' => ["El conjuro {$entry['index']} no pertenece a esta clase."]]);
            $spell = $api->getSpell($entry['index']);
            if (!$spell || (int) ($spell['level'] ?? 0) > $maxLevel) throw ValidationException::withMessages(['spells' => ['Has seleccionado un conjuro de nivel superior al que puedes lanzar.']]);
            $spellRows[] = ['character_id' => $character->id, 'spell_index' => $spell['index'], 'spell_name' => $spell['name'], 'spell_level' => (int) $spell['level'], 'source_class_index' => $class->class_index, 'spell_type' => $known ? 'known' : 'prepared', 'is_known' => true, 'is_prepared' => $known ? false : (bool) ($entry['prepared'] ?? true), 'is_exception' => false, 'created_at' => now(), 'updated_at' => now()];
        }
        $cantrips = collect($spellRows)->where('spell_level', 0)->count();
        $maxCantrips = (int) data_get($progress, 'spellcasting.cantrips_known', 0);
        if ($cantrips > $maxCantrips) throw ValidationException::withMessages(['spells' => ["Esta clase permite conocer {$maxCantrips} trucos en su nivel actual."]]);
        if ($known) {
            $maxKnown = (int) data_get($progress, 'spellcasting.spells_known', 0);
            $nonCantrips = collect($spellRows)->where('spell_level', '>', 0)->count();
            if ($maxKnown > 0 && $nonCantrips > $maxKnown) throw ValidationException::withMessages(['spells' => ["Esta clase permite conocer {$maxKnown} conjuros de nivel 1 o superior en su nivel actual."]]);
        } else {
            $ability = $subclassCaster ? 'int' : data_get($details, 'spellcasting.spellcasting_ability.index');
            $abilityColumn = ['str'=>'strength','dex'=>'dexterity','con'=>'constitution','int'=>'intelligence','wis'=>'wisdom','cha'=>'charisma'][$ability] ?? null;
            $abilityModifier = $abilityColumn ? (int) floor((((int) $character->{$abilityColumn} + (int) $character->{$abilityColumn . '_bonus'}) - 10) / 2) : 0;
            $preparedLimit = match ($class->class_index) {
                'paladin' => max(1, intdiv((int) $class->level, 2) + $abilityModifier),
                'cleric', 'druid', 'wizard' => max(1, (int) $class->level + $abilityModifier),
                default => 0,
            };
            $preparedCount = collect($spellRows)->where('spell_level', '>', 0)->where('is_prepared', true)->count();
            if ($preparedLimit > 0 && $preparedCount > $preparedLimit) throw ValidationException::withMessages(['spells' => ["Puedes preparar como máximo {$preparedLimit} conjuros de nivel 1 o superior con esta clase."]]);
        }
        DB::transaction(function () use ($character, $class, $spellRows) {
            $character->spells()->where('source_class_index', $class->class_index)->delete();
            if ($spellRows) $character->spells()->insert($spellRows);
        });
        return response()->json(['message' => 'Conjuros guardados.', 'spells' => $character->spells()->where('source_class_index', $class->class_index)->get()]);
    }

    public function slots(Request $request, int $characterId, SpellcastingCalculator $calculator)
    {
        $character = $this->accessible($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado.'], 404);
        $character->load('characterClasses');
        $summary = $calculator->summary($character);
        foreach ($summary['normal_slots'] as $slot) {
            $record = $character->spellSlots()->firstOrNew(['slot_type' => 'normal', 'spell_level' => $slot['spell_level']]);
            $record->total_slots = $slot['total_slots'];
            $record->used_slots = min((int) ($record->used_slots ?? 0), $record->total_slots);
            $record->save();
        }
        if ($summary['pact_slots']) {
            $character->spellSlots()->where('slot_type', 'pact')->where('spell_level', '!=', $summary['pact_slots']['slot_level'])->delete();
            $record = $character->spellSlots()->firstOrNew(['slot_type' => 'pact', 'spell_level' => $summary['pact_slots']['slot_level']]);
            $record->total_slots = $summary['pact_slots']['total_slots'];
            $record->used_slots = min((int) ($record->used_slots ?? 0), $record->total_slots);
            $record->save();
        }
        return response()->json($summary + ['saved_slots' => $character->spellSlots()->orderBy('slot_type')->orderBy('spell_level')->get()]);
    }

    public function updateSlot(Request $request, int $characterId, int $slotId)
    {
        $character = $this->accessible($request, $characterId);
        $slot = $character?->spellSlots()->find($slotId);
        if (!$slot) return response()->json(['message' => 'Espacio de conjuro no encontrado.'], 404);
        $data = $request->validate(['used_slots' => ['required', 'integer', 'min:0', 'max:' . (int) $slot->total_slots]]);
        $slot->update(['used_slots' => $data['used_slots']]);
        return response()->json($slot->fresh());
    }

    private function subclassLevel(string $class): int
    {
        return ['barbarian'=>3,'bard'=>3,'cleric'=>1,'druid'=>2,'fighter'=>3,'monk'=>3,'paladin'=>3,'ranger'=>3,'rogue'=>3,'sorcerer'=>1,'warlock'=>1,'wizard'=>2][$class] ?? 3;
    }

    private function accessible(Request $request, int $id): ?Character
    {
        $character = Character::with('campaigns')->find($id);
        if (!$character) return null;
        if ((int) $character->user_id === (int) $request->user()->id) return $character;
        $ids = $character->campaigns->pluck('id');
        return $ids->isNotEmpty() && $request->user()->campaigns()->whereIn('campaigns.id', $ids)->exists() ? $character : null;
    }
}
