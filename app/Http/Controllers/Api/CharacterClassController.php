<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterClass;
use App\Services\CharacterSheetCalculator;
use App\Services\DndApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CharacterClassController extends Controller
{
    public function index(Request $request, $characterId)
    {
        $character = $this->findAccessibleCharacter($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado o sin acceso.'], 404);

        return response()->json($character->characterClasses()->with('levelUps')->orderByDesc('is_primary')->get());
    }

    /** Añade una clase secundaria siguiendo las reglas de multiclase 2014. */
    public function store(Request $request, $characterId)
    {
        $character = $this->findAccessibleCharacter($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado o sin acceso.'], 404);

        $data = $request->validate([
            'class_index' => ['required', 'string', 'max:100'],
            'class_proficiencies' => ['sometimes', 'array'],
            'class_proficiencies.*' => ['string', 'max:150'],
            'hp_method' => ['required', 'in:average,manual'],
            'base_hp_gained' => ['required_if:hp_method,manual', 'integer', 'min:1', 'max:12'],
        ]);

        if ($character->characterClasses()->where('class_index', $data['class_index'])->exists()) {
            throw ValidationException::withMessages(['class_index' => ['El personaje ya tiene niveles en esa clase.']]);
        }

        $character->load('characterClasses');
        $primary = $character->characterClasses->firstWhere('is_primary', true);
        if (!$primary) {
            throw ValidationException::withMessages(['class_index' => ['No se ha encontrado la clase inicial del personaje.']]);
        }

        $api = app(DndApiService::class);
        $class = $api->getClass($data['class_index']);
        if (!$class) throw ValidationException::withMessages(['class_index' => ['La clase no existe en D&D 5e 2014.']]);

        $this->validateMulticlassPrerequisites($character, $class, $api);
        $selected = array_values(array_unique($data['class_proficiencies'] ?? []));
        $allowed = collect(data_get($class, 'multi_classing.proficiencies', []))->pluck('index')->filter()->all();
        $choices = data_get($class, 'multi_classing.proficiency_choices', []);
        $required = collect($choices)->sum(fn ($choice) => (int) ($choice['choose'] ?? 0));
        $choiceOptions = collect($choices)->flatMap(fn ($choice) => collect(data_get($choice, 'from.options', data_get($choice, 'from.option_set.options', [])))
            ->map(fn ($option) => data_get($option, 'item.index', data_get($option, 'index'))))->filter()->unique()->all();

        if (count($selected) !== $required || collect($selected)->diff($choiceOptions)->isNotEmpty()) {
            if ($required > 0 || $selected) {
                throw ValidationException::withMessages(['class_proficiencies' => ['Selecciona exactamente las competencias permitidas por las reglas de multiclase de esta clase.']]);
            }
        }

        if ((int) $character->level >= 20) {
            throw ValidationException::withMessages(['level' => ['Un personaje no puede superar el nivel total 20.']]);
        }

        $die = (int) ($class['hit_die'] ?? 0);
        $baseHp = $data['hp_method'] === 'average' ? max(1, (int) floor($die / 2)) : (int) $data['base_hp_gained'];
        if ($die < 4 || $die > 12 || $baseHp > $die) {
            throw ValidationException::withMessages(['base_hp_gained' => ['El resultado de puntos de golpe no es válido para el dado de esta clase.']]);
        }
        $constitution = app(CharacterSheetCalculator::class)->abilityModifier($character, 'constitution') ?? 0;
        $constitutionApplied = max(0, $constitution);
        $totalHp = max(1, $baseHp + $constitutionApplied);

        $newClass = DB::transaction(function () use ($character, $class, $data, $allowed, $selected, $api, $die, $baseHp, $constitutionApplied, $totalHp) {
            $record = $character->characterClasses()->create([
                'class' => $class['name'],
                'class_index' => $class['index'],
                'level' => 1,
                'is_primary' => false,
                'hit_die' => $die,
            ]);
            $character->levelUps()->create([
                'character_class_id' => $record->id,
                'class_level' => 1,
                'hit_die' => $die,
                'hp_method' => $data['hp_method'],
                'base_hp_gained' => $baseHp,
                'constitution_modifier_applied' => $constitutionApplied,
                'total_hp_gained' => $totalHp,
            ]);
            $oldCurrentHp = $character->current_hit_points;
            $character->maximum_hit_points = (int) ($character->maximum_hit_points ?? 0) + $totalHp;
            $character->current_hit_points = $oldCurrentHp === null
                ? $character->maximum_hit_points
                : $oldCurrentHp + $totalHp;

            // En multiclase NO se conceden las tiradas de salvación de la clase.
            foreach ($allowed as $index) {
                $detail = $api->getProficiency($index);
                if (!$detail) continue;
                $category = $this->category($index);
                if (!$category) continue;
                $character->proficiencies()->firstOrCreate(
                    ['category' => $category, 'proficiency_index' => $index],
                    ['proficiency_name' => $detail['name'] ?? $index, 'source_type' => 'multiclass', 'source_index' => $class['index'], 'proficiency_level' => 1, 'is_active' => true]
                );
            }

            foreach ($selected as $index) {
                $detail = $api->getProficiency($index);
                $category = $this->category($index);
                if (!$detail || !$category) continue;
                $character->proficiencies()->firstOrCreate(
                    ['category' => $category, 'proficiency_index' => $index],
                    ['proficiency_name' => $detail['name'] ?? $index, 'source_type' => 'multiclass', 'source_index' => $class['index'], 'proficiency_level' => 1, 'is_active' => true]
                );
            }

            $this->syncTotalLevel($character);
            return $record;
        });

        return response()->json(['message' => 'Multiclase añadida. Se ha aplicado únicamente el paquete de competencias de multiclase.', 'character_class' => $newClass->load('levelUps'), 'character' => $character->fresh()], 201);
    }

    /** Devuelve las decisiones que corresponden al siguiente nivel de esta clase. */
    public function levelUpOptions(Request $request, $characterId, $classId)
    {
        $character = $this->findAccessibleCharacter($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado o sin acceso.'], 404);
        $classRow = $character->characterClasses()->find($classId);
        if (!$classRow) return response()->json(['message' => 'Clase no encontrada.'], 404);
        if ((int) $character->level >= 20 || (int) $classRow->level >= 20) {
            return response()->json(['message' => 'El personaje o esta clase ya ha alcanzado el nivel 20.'], 422);
        }

        $api = app(DndApiService::class);
        $nextLevel = (int) $classRow->level + 1;
        $classLevel = $api->getClassLevel($classRow->class_index, $nextLevel) ?? [];
        $features = collect(data_get($classLevel, 'features', []))->map(function ($feature) use ($api) {
            $index = data_get($feature, 'index');
            if ($index) {
                try {
                    $detail = $api->getFeature($index);
                    if ($detail) return array_merge($feature, ['desc' => $detail['desc'] ?? []]);
                } catch (\Throwable $exception) {
                    // Mantener el nombre del rasgo si el catálogo no devuelve su descripción.
                }
            }
            return $feature;
        });
        $hasAsi = $features->contains(function ($feature) {
            $index = strtolower((string) data_get($feature, 'index', ''));
            $name = strtolower((string) data_get($feature, 'name', ''));
            return str_contains($index, 'ability-score-improvement') || str_contains($name, 'ability score improvement');
        });

        $classDetails = $api->getClass($classRow->class_index) ?? [];
        $subclassLevel = ['barbarian'=>3,'bard'=>3,'cleric'=>1,'druid'=>2,'fighter'=>3,'monk'=>3,'paladin'=>3,'ranger'=>3,'rogue'=>3,'sorcerer'=>1,'warlock'=>1,'wizard'=>2][$classRow->class_index] ?? 3;
        $subclasses = (!$classRow->subclass_index && $nextLevel >= $subclassLevel)
            ? collect(data_get($classDetails, 'subclasses', []))->map(fn ($item) => ['index' => $item['index'] ?? null, 'name' => $item['name'] ?? $item['index']])->values()
            : collect();

        try { $featCatalog = $api->getFeats(); } catch (\Throwable $exception) { $featCatalog = ['results' => []]; }
        $feats = collect(data_get($featCatalog, 'results', []))->map(fn ($feat) => [
            'index' => $feat['index'] ?? null,
            'name' => $feat['name'] ?? $feat['index'] ?? 'Dote',
            'url' => $feat['url'] ?? null,
        ])->filter(fn ($feat) => $feat['index'])->values();

        return response()->json([
            'class' => $classRow->class,
            'class_index' => $classRow->class_index,
            'current_class_level' => (int) $classRow->level,
            'next_class_level' => $nextLevel,
            'character_level_after' => (int) $character->level + 1,
            'hit_die' => (int) $classRow->hit_die,
            'class_level' => $classLevel,
            'features' => $features->values(),
            'has_asi' => $hasAsi,
            'subclass_required' => $subclasses->isNotEmpty(),
            'subclasses' => $subclasses,
            'feats' => $feats,
            'ability_scores' => app(CharacterSheetCalculator::class)->abilityScores($character),
        ]);
    }

    /** Registra una subida de nivel en una clase concreta. */
    public function levelUp(Request $request, $characterId, $classId)
    {
        $character = $this->findAccessibleCharacter($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado o sin acceso.'], 404);
        $classRow = $character->characterClasses()->find($classId);
        if (!$classRow) return response()->json(['message' => 'Clase no encontrada.'], 404);

        $data = $request->validate([
            'hp_method' => ['required', 'in:average,manual'],
            'base_hp_gained' => ['required_if:hp_method,manual', 'integer', 'min:1', 'max:12'],
            'subclass_index' => ['sometimes', 'nullable', 'string', 'max:100'],
            'feat_index' => ['sometimes', 'nullable', 'string', 'max:100'],
            'feat_ability_increase' => ['sometimes', 'nullable', 'in:strength,dexterity,constitution,intelligence,wisdom,charisma'],
            'ability_score_increases' => ['sometimes', 'array', 'max:2'],
            'ability_score_increases.*.ability' => ['required_with:ability_score_increases', 'in:strength,dexterity,constitution,intelligence,wisdom,charisma'],
            'ability_score_increases.*.increase' => ['required_with:ability_score_increases', 'integer', 'in:1,2'],
        ]);

        if ((int) $character->level >= 20) throw ValidationException::withMessages(['level' => ['El personaje ya ha alcanzado el nivel 20.']]);
        if ((int) $classRow->level >= 20) throw ValidationException::withMessages(['class_id' => ['Esta clase ya ha alcanzado el nivel 20.']]);

        $nextClassLevel = (int) $classRow->level + 1;
        $selectedSubclass = null;
        $api = app(DndApiService::class);
        $classDetails = $api->getClass($classRow->class_index);
        $subclassLevel = ['barbarian'=>3,'bard'=>3,'cleric'=>1,'druid'=>2,'fighter'=>3,'monk'=>3,'paladin'=>3,'ranger'=>3,'rogue'=>3,'sorcerer'=>1,'warlock'=>1,'wizard'=>2][$classRow->class_index] ?? 3;
        if (!$classRow->subclass_index && $nextClassLevel >= $subclassLevel) {
            $subclassIndex = $data['subclass_index'] ?? null;
            $valid = collect(data_get($classDetails, 'subclasses', []))->contains(fn ($item) => ($item['index'] ?? null) === $subclassIndex);
            if (!$valid) throw ValidationException::withMessages(['subclass_index' => ['Debes seleccionar una subclase válida para alcanzar este nivel.']]);
            $selectedSubclass = $api->getSubclass($subclassIndex);
            if (!$selectedSubclass) throw ValidationException::withMessages(['subclass_index' => ['No se pudo consultar la subclase seleccionada.']]);
        }

        $classLevelDetails = $api->getClassLevel($classRow->class_index, $nextClassLevel) ?? [];
        $levelFeatures = collect(data_get($classLevelDetails, 'features', []));
        $hasAsi = $levelFeatures->contains(function ($feature) {
            return str_contains(strtolower((string) data_get($feature, 'index', '')), 'ability-score-improvement')
                || str_contains(strtolower((string) data_get($feature, 'name', '')), 'ability score improvement');
        });
        $feat = null;
        $featAsi = [];
        $asi = $data['ability_score_increases'] ?? [];
        if (!$hasAsi && (!empty($data['feat_index']) || !empty($asi))) {
            throw ValidationException::withMessages(['ability_score_increases' => ['Este nivel no concede una mejora de característica ni una dote.']]);
        }
        if ($hasAsi) {
            if (!empty($data['feat_index']) && !empty($asi)) {
                throw ValidationException::withMessages(['feat_index' => ['Debes elegir una dote o mejorar características, no ambas opciones.']]);
            }
            if (empty($data['feat_index']) && empty($asi)) {
                throw ValidationException::withMessages(['feat_index' => ['Debes elegir una dote o distribuir la mejora de características.']]);
            }
            if (!empty($data['feat_index'])) {
                $feat = $api->getFeat($data['feat_index']);
                if (!$feat) throw ValidationException::withMessages(['feat_index' => ['La dote seleccionada no existe en el catálogo 2014.']]);
                $calculator = app(CharacterSheetCalculator::class);
                foreach (data_get($feat, 'prerequisites', []) as $prerequisite) {
                    $abilityIndex = data_get($prerequisite, 'ability_score.index');
                    $minimum = (int) data_get($prerequisite, 'minimum_score', 0);
                    $abilityMap = ['str' => 'strength', 'dex' => 'dexterity', 'con' => 'constitution', 'int' => 'intelligence', 'wis' => 'wisdom', 'cha' => 'charisma'];
                    if ($abilityIndex && isset($abilityMap[$abilityIndex])
                        && (int) $calculator->effectiveScore($character, $abilityMap[$abilityIndex]) < $minimum) {
                        throw ValidationException::withMessages(['feat_index' => ['No cumples los requisitos de características de esta dote.']]);
                    }
                }
                $featDescription = implode(' ', (array) data_get($feat, 'desc', []));
                if (!empty($data['feat_ability_increase'])) {
                    $abilityLabels = ['strength'=>'Strength','dexterity'=>'Dexterity','constitution'=>'Constitution','intelligence'=>'Intelligence','wisdom'=>'Wisdom','charisma'=>'Charisma'];
                    $selectedLabel = $abilityLabels[$data['feat_ability_increase']];
                    $allowsAny = preg_match('/one ability score of your choice/i', $featDescription);
                    $allowsSelected = preg_match('/(increase|increases) your .{0,100}' . preg_quote($selectedLabel, '/') . '.{0,100} score by 1/i', $featDescription);
                    if (!$allowsAny && !$allowsSelected) {
                        throw ValidationException::withMessages(['feat_ability_increase' => ['Esta dote no concede esa mejora de característica.']]);
                    }
                    if ((int) $calculator->effectiveScore($character, $data['feat_ability_increase']) >= 20) {
                        throw ValidationException::withMessages(['feat_ability_increase' => ['La característica no puede superar 20.']]);
                    }
                    $featAsi[] = ['ability' => $data['feat_ability_increase'], 'increase' => 1];
                }
                if (preg_match('/(increase|increases) your .{1,120} score by 1|one ability score of your choice/i', $featDescription)
                    && empty($data['feat_ability_increase'])) {
                    throw ValidationException::withMessages(['feat_ability_increase' => ['Esta dote concede +1 a una característica; selecciona cuál.']]);
                }
            } else {
                $sum = collect($asi)->sum(fn ($item) => (int) ($item['increase'] ?? 0));
                $abilities = collect($asi)->pluck('ability');
                if (!in_array(count($asi), [1, 2], true) || !in_array($sum, [2], true) || $abilities->unique()->count() !== count($asi)
                    || (count($asi) === 1 && (int) $asi[0]['increase'] !== 2)
                    || (count($asi) === 2 && collect($asi)->contains(fn ($item) => (int) $item['increase'] !== 1))) {
                    throw ValidationException::withMessages(['ability_score_increases' => ['La mejora debe ser +2 a una característica o +1 a dos características distintas.']]);
                }
                $calculator = app(CharacterSheetCalculator::class);
                $allAbilityIncreases = array_merge($asi, $featAsi);
            foreach ($allAbilityIncreases as $choice) {
                    $current = $calculator->effectiveScore($character, $choice['ability']);
                    if ($current === null || $current + (int) $choice['increase'] > 20) {
                        throw ValidationException::withMessages(['ability_score_increases' => ['Ninguna característica puede superar 20 mediante esta mejora.']]);
                    }
                }
            }
        }

        $die = (int) $classRow->hit_die;
        if ($die < 4 || $die > 12) throw ValidationException::withMessages(['class_id' => ['El dado de golpe de esta clase no es válido.']]);
        $base = $data['hp_method'] === 'average' ? max(1, (int) floor($die / 2)) : (int) $data['base_hp_gained'];
        if ($base > $die) throw ValidationException::withMessages(['base_hp_gained' => ['El resultado no puede superar el dado de golpe de la clase.']]);

        $calculator = app(CharacterSheetCalculator::class);
        $constitution = $calculator->abilityModifier($character, 'constitution') ?? 0;
        $constitutionApplied = max(0, $constitution);
        $totalGain = max(1, $base + $constitutionApplied);

        $result = DB::transaction(function () use ($character, $classRow, $data, $die, $base, $constitutionApplied, $totalGain, $selectedSubclass, $feat, $asi, $featAsi) {
            $nextClassLevel = (int) $classRow->level + 1;
            if ($selectedSubclass) $classRow->update(['subclass_index' => $selectedSubclass['index'], 'subclass_name' => $selectedSubclass['name']]);
            $classRow->update(['level' => $nextClassLevel]);
            $character->level = (int) $character->characterClasses()->sum('level');
            $character->proficiency_bonus = match (true) {
                $character->level <= 4 => 2, $character->level <= 8 => 3,
                $character->level <= 12 => 4, $character->level <= 16 => 5, default => 6,
            };
            if ($character->maximum_hit_points === null) $character->maximum_hit_points = 0;
            $character->maximum_hit_points += $totalGain;
            if ($character->current_hit_points === null) $character->current_hit_points = $character->maximum_hit_points;
            else $character->current_hit_points += $totalGain;
            $allAbilityIncreases = array_merge($asi, $featAsi);
            foreach ($allAbilityIncreases as $choice) {
                $ability = $choice['ability'];
                $character->{$ability} = (int) $character->{$ability} + (int) $choice['increase'];
            }
            $character->save();

            return $character->levelUps()->create([
                'character_class_id' => $classRow->id,
                'class_level' => $nextClassLevel,
                'hit_die' => $die,
                'hp_method' => $data['hp_method'],
                'base_hp_gained' => $base,
                'constitution_modifier_applied' => $constitutionApplied,
                'total_hp_gained' => $totalGain,
                'feat_index' => $feat['index'] ?? null,
                'feat_name' => $feat['name'] ?? null,
                'feat_description' => isset($feat['desc']) ? implode("\n", (array) $feat['desc']) : null,
                'ability_score_increases' => $allAbilityIncreases ?: null,
            ]);
        });

        return response()->json([
            'message' => 'Nivel añadido correctamente.',
            'level_up' => $result,
            'character_class' => $classRow->fresh()->load('levelUps'),
            'character' => $character->fresh()->load('characterClasses'),
            'next_class_level' => $classRow->level,
        ]);
    }

    public function show(Request $request, $characterId, $classId)
    {
        $character = $this->findAccessibleCharacter($request, $characterId);
        if (!$character) return response()->json(['message' => 'Personaje no encontrado o sin acceso.'], 404);
        $class = $character->characterClasses()->with('levelUps')->find($classId);
        return $class ? response()->json($class) : response()->json(['message' => 'Clase no encontrada.'], 404);
    }

    public function update(Request $request, $characterId, $classId)
    {
        return response()->json(['message' => 'Los niveles no se editan directamente. Utiliza la acción de subida de nivel para conservar el historial y aplicar los puntos de golpe.'], 405);
    }

    public function destroy(Request $request, $characterId, $classId)
    {
        return response()->json(['message' => 'No se permite eliminar una clase con progresión registrada. La corrección de niveles se realizará mediante una herramienta de edición de personaje.'], 405);
    }

    private function validateMulticlassPrerequisites(Character $character, array $class, DndApiService $api): void
    {
        $classesToCheck = [$class];
        foreach ($character->characterClasses as $existing) {
            if ($existing->class_index) {
                $existingDetails = $api->getClass($existing->class_index);
                if ($existingDetails) $classesToCheck[] = $existingDetails;
            }
        }
        $calculator = app(CharacterSheetCalculator::class);
        $scores = [
            'str' => $calculator->effectiveScore($character, 'strength'),
            'dex' => $calculator->effectiveScore($character, 'dexterity'),
            'con' => $calculator->effectiveScore($character, 'constitution'),
            'int' => $calculator->effectiveScore($character, 'intelligence'),
            'wis' => $calculator->effectiveScore($character, 'wisdom'),
            'cha' => $calculator->effectiveScore($character, 'charisma'),
        ];
        foreach ($classesToCheck as $classDetails) {
            $requirements = collect(data_get($classDetails, 'multi_classing.prerequisites', []));
            if ($requirements->isEmpty()) continue;
            // El guerrero puede cumplir Fuerza 13 O Destreza 13; los demás requisitos se exigen conjuntamente.
            if (($classDetails['index'] ?? '') === 'fighter') {
                $valid = $requirements->contains(function ($requirement) use ($scores) {
                    $ability = strtolower(substr((string) data_get($requirement, 'ability_score.index', ''), 0, 3));
                    return isset($scores[$ability]) && $scores[$ability] !== null && $scores[$ability] >= (int) data_get($requirement, 'minimum_score', 0);
                });
                if (!$valid) throw ValidationException::withMessages(['class_index' => ['Para multiclasear como guerrero necesitas Fuerza 13 o Destreza 13.']]);
                continue;
            }
            foreach ($requirements as $requirement) {
                $ability = strtolower(substr((string) data_get($requirement, 'ability_score.index', ''), 0, 3));
                $minimum = (int) data_get($requirement, 'minimum_score', 0);
                if (!isset($scores[$ability]) || $scores[$ability] === null || $scores[$ability] < $minimum) {
                    $label = data_get($requirement, 'ability_score.name', strtoupper($ability));
                    throw ValidationException::withMessages(['class_index' => ["No cumples el requisito de multiclase: {$label} {$minimum}."]]);
                }
            }
        }
    }

    private function syncTotalLevel(Character $character): void
    {
        $character->level = (int) $character->characterClasses()->sum('level');
        $character->proficiency_bonus = match (true) {
            $character->level <= 4 => 2, $character->level <= 8 => 3,
            $character->level <= 12 => 4, $character->level <= 16 => 5, default => 6,
        };
        $character->save();
    }

    private function category(string $index): ?string
    {
        return match (true) {
            str_starts_with($index, 'skill-') => 'skill',
            str_starts_with($index, 'weapon-') => 'weapon',
            str_starts_with($index, 'armor-') => 'armor',
            str_starts_with($index, 'tool-') => 'tool',
            str_starts_with($index, 'language-') => 'language',
            default => null,
        };
    }

    private function findAccessibleCharacter(Request $request, $id): ?Character
    {
        $character = Character::with('campaigns')->find($id);
        if (!$character) return null;
        if ((int) $character->user_id === (int) $request->user()->id) return $character;
        $campaignIds = $character->campaigns->pluck('id');
        return $campaignIds->isNotEmpty() && $request->user()->campaigns()->whereIn('campaigns.id', $campaignIds)->exists() ? $character : null;
    }
}
