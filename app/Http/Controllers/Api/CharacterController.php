<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterProficiency;
use App\Services\AbilityScoreValidator;
use App\Services\CharacterSheetCalculator;
use App\Services\DndApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\ValidationException;

class CharacterController extends Controller
{
    /**
     * Devuelve los personajes creados por el usuario autenticado.
     */
    public function myCharacters(Request $request)
    {
        $characters = Character::where('user_id', $request->user()->id)
            ->with(['user', 'characterClasses', 'campaigns'])
            ->latest()
            ->get();

        $calculator = app(CharacterSheetCalculator::class);

        foreach ($characters as $character) {
            $character->setAttribute(
                'sheet',
                $calculator->summary($character)
            );
        }

        return response()->json($characters);
    }

    /**
     * Crea un personaje independiente, todavía sin campaña.
     */
    public function storeStandalone(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string'],

            'personality_trait' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'ideal' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'bond' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'flaw' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'notes' => [
                'sometimes', 'nullable', 'string', 'max:10000',
            ],
        ]);

        $catalogData = $this->validateCatalogSelection($request);
        $classData = $this->validateInitialClassSelection($request);
        $abilityData = $this->validateAbilityScores($request);

        $validated = array_merge(
            $validated,
            $catalogData,
            $classData,
            $abilityData
        );

        $proficiencyBonus = $this->calculateProficiencyBonus(
            (int) $validated['level']
        );

        $character = Character::create([
            'campaign_id' => null,
            'user_id' => $request->user()->id,

            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'race_index' => $validated['race_index'] ?? null,
            'subrace_index' => $validated['subrace_index'] ?? null,
            'background_index' => $validated['background_index'] ?? null,
            'background' => $validated['background'] ?? null,

            'level' => $validated['level'],
            'proficiency_bonus' => $proficiencyBonus,
            'description' => $validated['description'] ?? null,

            'personality_trait' => $validated['personality_trait'] ?? null,
            'ideal' => $validated['ideal'] ?? null,
            'bond' => $validated['bond'] ?? null,
            'flaw' => $validated['flaw'] ?? null,
            'notes' => $validated['notes'] ?? null,

            'strength' => $validated['strength'] ?? null,
            'dexterity' => $validated['dexterity'] ?? null,
            'constitution' => $validated['constitution'] ?? null,
            'intelligence' => $validated['intelligence'] ?? null,
            'wisdom' => $validated['wisdom'] ?? null,
            'charisma' => $validated['charisma'] ?? null,

            'strength_bonus' => $validated['strength_bonus'] ?? 0,
            'dexterity_bonus' => $validated['dexterity_bonus'] ?? 0,
            'constitution_bonus' => $validated['constitution_bonus'] ?? 0,
            'intelligence_bonus' => $validated['intelligence_bonus'] ?? 0,
            'wisdom_bonus' => $validated['wisdom_bonus'] ?? 0,
            'charisma_bonus' => $validated['charisma_bonus'] ?? 0,

            'ability_score_method' => $validated['ability_score_method'] ?? null,
        ]);

        $this->persistInitialClassData($character, $validated);
        $this->persistCatalogProficiencies($character, $validated);

        return response()->json([
            'message' => 'Personaje creado correctamente.',
            'character' => $character->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ], 201);
    }

    /**
     * Devuelve los personajes asignados a una campaña.
     */
    public function index(Request $request, $campaignId)
    {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (! $campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $characters = $campaign->characters()
            ->with(['user', 'characterClasses', 'campaigns'])
            ->get();

        $calculator = app(CharacterSheetCalculator::class);

        foreach ($characters as $character) {
            $character->setAttribute(
                'sheet',
                $calculator->summary($character)
            );
        }

        return response()->json($characters);
    }

    /**
     * Crea un personaje nuevo y lo asigna a una campaña.
     */
    public function store(Request $request, $campaignId)
    {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (! $campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string'],

            'personality_trait' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'ideal' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'bond' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'flaw' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'notes' => [
                'sometimes', 'nullable', 'string', 'max:10000',
            ],
        ]);

        $catalogData = $this->validateCatalogSelection($request);
        $classData = $this->validateInitialClassSelection($request);
        $abilityData = $this->validateAbilityScores($request);

        $validated = array_merge(
            $validated,
            $catalogData,
            $classData,
            $abilityData
        );

        $proficiencyBonus = $this->calculateProficiencyBonus(
            (int) $validated['level']
        );

        $character = DB::transaction(function () use (
            $request,
            $campaign,
            $validated,
            $proficiencyBonus
        ) {
            $character = Character::create([
                'campaign_id' => null,
                'user_id' => $request->user()->id,

                'name' => $validated['name'],
                'race' => $validated['race'] ?? null,
                'race_index' => $validated['race_index'] ?? null,
                'subrace_index' => $validated['subrace_index'] ?? null,
                'background_index' => $validated['background_index'] ?? null,
                'background' => $validated['background'] ?? null,

                'level' => $validated['level'],
                'proficiency_bonus' => $proficiencyBonus,
                'description' => $validated['description'] ?? null,

                'personality_trait' => $validated['personality_trait'] ?? null,
                'ideal' => $validated['ideal'] ?? null,
                'bond' => $validated['bond'] ?? null,
                'flaw' => $validated['flaw'] ?? null,
                'notes' => $validated['notes'] ?? null,

                'strength' => $validated['strength'] ?? null,
                'dexterity' => $validated['dexterity'] ?? null,
                'constitution' => $validated['constitution'] ?? null,
                'intelligence' => $validated['intelligence'] ?? null,
                'wisdom' => $validated['wisdom'] ?? null,
                'charisma' => $validated['charisma'] ?? null,

                'strength_bonus' => $validated['strength_bonus'] ?? 0,
                'dexterity_bonus' => $validated['dexterity_bonus'] ?? 0,
                'constitution_bonus' => $validated['constitution_bonus'] ?? 0,
                'intelligence_bonus' => $validated['intelligence_bonus'] ?? 0,
                'wisdom_bonus' => $validated['wisdom_bonus'] ?? 0,
                'charisma_bonus' => $validated['charisma_bonus'] ?? 0,

                'ability_score_method' => $validated['ability_score_method'] ?? null,
            ]);

            $character->campaigns()->attach($campaign->id);
            $this->persistInitialClassData($character, $validated);
            $this->persistCatalogProficiencies($character, $validated);

            return $character;
        });

        return response()->json([
            'message' => 'Personaje creado correctamente.',
            'character' => $character->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ], 201);
    }

    /**
     * Asigna un personaje existente a una campaña.
     * Solo el propietario puede realizar la asignación.
     */
    public function assignCharacter(
        Request $request,
        $campaignId,
        $characterId
    ) {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (! $campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $character = Character::where('user_id', $request->user()->id)
            ->find($characterId);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado o no eres su propietario.',
            ], 404);
        }

        if ($character->campaigns()->whereKey($campaign->id)->exists()) {
            return response()->json([
                'message' => 'El personaje ya está asignado a esta campaña.',
            ], 422);
        }

        $character->campaigns()->attach($campaign->id);

        return response()->json([
            'message' => 'Personaje asignado correctamente a la campaña.',
            'character' => $character->fresh()->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ]);
    }

    /**
     * Quita un personaje de una campaña sin borrar la ficha.
     */
    public function removeCharacterFromCampaign(
        Request $request,
        $campaignId,
        $characterId
    ) {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (! $campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $character = Character::where('user_id', $request->user()->id)
            ->find($characterId);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado o no eres su propietario.',
            ], 404);
        }

        if (! $character->campaigns()->whereKey($campaign->id)->exists()) {
            return response()->json([
                'message' => 'El personaje no está asignado a esta campaña.',
            ], 422);
        }

        DB::transaction(function () use ($character, $campaign) {
            $character->campaigns()->detach($campaign->id);

            // Compatibilidad temporal con la columna antigua.
            if ((int) $character->campaign_id === (int) $campaign->id) {
                $character->campaign_id = null;
                $character->save();
            }
        });

        return response()->json([
            'message' => 'Personaje retirado de la campaña. La ficha se conserva.',
            'character' => $character->fresh()->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ]);
    }

    /**
     * Muestra un personaje si el usuario tiene permiso.
     */
    public function show(Request $request, $id)
    {
        $character = Character::with([
            'user',
            'characterClasses.levelUps',
            'campaigns',
            'levelUps.characterClass',
            'proficiencies',
            'proficiencyOverrides',
            'inventory',
            'currency',
            'spells',
            'spellSlots',
        ])->find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        if (! $this->hasCharacterAccess($request, $character)) {
            return response()->json([
                'message' => 'No tienes acceso a este personaje.',
            ], 404);
        }

        $calculator = app(CharacterSheetCalculator::class);

        $character->setAttribute(
            'sheet',
            $calculator->summary($character)
        );

        return response()->json($character);
    }

    /**
     * Actualiza un personaje si el usuario tiene permiso.
     */
    public function update(Request $request, $id)
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        if (! $this->hasCharacterAccess($request, $character)) {
            return response()->json([
                'message' => 'No tienes acceso a este personaje.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string'],

            'personality_trait' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'ideal' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'bond' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'flaw' => [
                'sometimes', 'nullable', 'string', 'max:5000',
            ],
            'notes' => [
                'sometimes', 'nullable', 'string', 'max:10000',
            ],
        ]);

        $catalogData = $this->validateCatalogSelection($request);
        $abilityData = $this->validateAbilityScores($request);

        $validated = array_merge(
            $validated,
            $catalogData,
            $abilityData
        );

        $proficiencyBonus = $this->calculateProficiencyBonus(
            (int) $validated['level']
        );

        $characterData = [
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'race_index' => $validated['race_index'] ?? null,
            'subrace_index' => $validated['subrace_index'] ?? null,
            'background_index' => $validated['background_index'] ?? null,
            'background' => $validated['background'] ?? null,
            'level' => $validated['level'],
            'proficiency_bonus' => $proficiencyBonus,
            'description' => $validated['description'] ?? null,
        ];

        foreach ([
            'personality_trait',
            'ideal',
            'bond',
            'flaw',
            'notes',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $characterData[$field] = $validated[$field];
            }
        }

        foreach ([
            'ability_score_method',
            'strength',
            'dexterity',
            'constitution',
            'intelligence',
            'wisdom',
            'charisma',
            'strength_bonus',
            'dexterity_bonus',
            'constitution_bonus',
            'intelligence_bonus',
            'wisdom_bonus',
            'charisma_bonus',
        ] as $field) {
            if (array_key_exists($field, $abilityData)) {
                $characterData[$field] = $abilityData[$field];
            }
        }

        $character->update($characterData);

        return response()->json([
            'message' => 'Personaje actualizado correctamente.',
            'character' => $character->fresh()->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ]);
    }

    /**
     * Elimina la ficha del personaje.
     */
    public function destroy(Request $request, $id)
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        // Solo el propietario puede borrar definitivamente la ficha.
        if ((int) $character->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Solo el propietario puede eliminar este personaje.',
            ], 403);
        }

        $character->delete();

        return response()->json([
            'message' => 'Personaje eliminado correctamente.',
        ]);
    }

    /**
     * Sube o sustituye el retrato de un personaje.
     * Solo el propietario puede modificar su imagen.
     */
    public function uploadImage(Request $request, $id)
    {
        $character = Character::find($id);

        if (! $character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

     $user = $request->user();

if (! $user) {
    return response()->json([
        'message' => 'No hay una sesión autenticada. Inicia sesión e inténtalo de nuevo.',
    ], 401);
}

if ((int) $character->user_id !== (int) $user->id) {
    return response()->json([
        'message' => 'Solo el propietario puede cambiar el retrato.',
    ], 403);
}

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $newPath = $validated['image']->store('characters', 'public');

        if (! $newPath) {
            return response()->json([
                'message' => 'No se pudo guardar la imagen.',
            ], 500);
        }

        $oldPath = $character->image_path;

        $character->image_path = $newPath;
        $character->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Retrato actualizado correctamente.',
            'character' => $character->fresh()->load([
                'user',
                'characterClasses',
                'campaigns',
            ]),
        ]);
    }

    /**
     * Comprueba si el usuario es propietario o miembro de una campaña
     * a la que está asignado el personaje.
     */
    private function hasCharacterAccess(
        Request $request,
        Character $character
    ): bool {
        $user = $request->user();

        if ((int) $character->user_id === (int) $user->id) {
            return true;
        }

        $campaignIds = $character->campaigns()
            ->pluck('campaigns.id');

        if ($campaignIds->isEmpty()) {
            return false;
        }

        return $user->campaigns()
            ->whereIn('campaigns.id', $campaignIds)
            ->exists();
    }

    /**
     * Valida la clase inicial y las elecciones de competencias contra la API 2014.
     */
    private function validateInitialClassSelection(Request $request): array
    {
        $data = $request->validate([
            'class_index' => ['required', 'string', 'max:100'],
            'class_proficiencies' => ['present', 'array'],
            'class_proficiencies.*' => ['required', 'string', 'max:150'],
        ]);

        $class = app(DndApiService::class)->getClass($data['class_index']);
        if (! $class) {
            throw ValidationException::withMessages([
                'class_index' => ['La clase seleccionada no existe en el catálogo D&D 5e 2014.'],
            ]);
        }

        $choices = $class['proficiency_choices'] ?? [];
        $requiredCount = collect($choices)->sum(fn ($choice) => (int) ($choice['choose'] ?? 0));
        $selected = array_values(array_unique($data['class_proficiencies']));

        if (count($selected) !== $requiredCount) {
            throw ValidationException::withMessages([
                'class_proficiencies' => ['Debes realizar exactamente todas las elecciones de competencias requeridas por la clase.'],
            ]);
        }

        $allowed = collect($choices)->flatMap(function ($choice) {
            return collect(data_get($choice, 'from.options', data_get($choice, 'from.option_set.options', [])))
                ->map(fn ($option) => data_get($option, 'item.index', data_get($option, 'index')));
        })->filter()->unique()->values()->all();

        foreach ($selected as $index) {
            if (! in_array($index, $allowed, true)) {
                throw ValidationException::withMessages([
                    'class_proficiencies' => ['Una de las competencias elegidas no está permitida para esta clase.'],
                ]);
            }
        }

        return [
            'class_index' => $class['index'],
            'class_name' => $class['name'],
            'class_hit_die' => (int) ($class['hit_die'] ?? 0),
            'class_details' => $class,
            'class_proficiencies' => $selected,
        ];
    }

    /**
     * Registra la clase inicial y sus competencias sin aplicar reglas de multiclase.
     */
    private function persistInitialClassData(Character $character, array $data): void
    {
        $class = $data['class_details'];
        $classRecord = $character->characterClasses()->create([
            'class' => $data['class_name'],
            'class_index' => $data['class_index'],
            'level' => (int) $data['level'],
            'is_primary' => true,
            'hit_die' => $data['class_hit_die'],
        ]);

        // Al crear una ficha de nivel superior, genera sus PG iniciales e historial
        // usando el máximo del dado al nivel 1 y el promedio acordado en niveles posteriores.
        $hitDie = (int) $data['class_hit_die'];
        $constitution = max(0, (int) floor((((int) ($character->constitution ?? 10) + (int) ($character->constitution_bonus ?? 0)) - 10) / 2));
        $firstLevelHp = $hitDie + $constitution;
        $character->maximum_hit_points = $firstLevelHp;
        $character->current_hit_points = $firstLevelHp;
        for ($classLevel = 2; $classLevel <= (int) $data['level']; $classLevel++) {
            $baseHp = max(1, (int) floor($hitDie / 2));
            $gainedHp = $baseHp + $constitution;
            $character->maximum_hit_points += $gainedHp;
            $character->current_hit_points += $gainedHp;
            $character->levelUps()->create([
                'character_class_id' => $classRecord->id,
                'class_level' => $classLevel,
                'hit_die' => $hitDie,
                'hp_method' => 'average',
                'base_hp_gained' => $baseHp,
                'constitution_modifier_applied' => $constitution,
                'total_hp_gained' => $gainedHp,
            ]);
        }
        $character->save();

        $records = [];
        foreach (($class['saving_throws'] ?? []) as $proficiency) {
            $records[] = $this->proficiencyRecord($proficiency, 'saving_throw', $data['class_index']);
        }
        foreach (($class['proficiencies'] ?? []) as $proficiency) {
            $records[] = $this->proficiencyRecord(
                $proficiency,
                $this->proficiencyCategory($proficiency['index'] ?? ''),
                $data['class_index']
            );
        }

        $selectedIndexes = $data['class_proficiencies'] ?? [];
        foreach (($class['proficiency_choices'] ?? []) as $choice) {
            $options = collect(data_get($choice, 'from.options', data_get($choice, 'from.option_set.options', [])))
                ->map(fn ($option) => data_get($option, 'item', $option));
            foreach ($options as $option) {
                if (in_array($option['index'] ?? null, $selectedIndexes, true)) {
                    $records[] = $this->proficiencyRecord(
                        $option,
                        $this->proficiencyCategory($option['index'] ?? ''),
                        $data['class_index']
                    );
                }
            }
        }

        foreach ($records as $record) {
            if (! empty($record['category']) && ! empty($record['proficiency_index'])) {
                $character->proficiencies()->firstOrCreate(
                    [
                        'category' => $record['category'],
                        'proficiency_index' => $record['proficiency_index'],
                    ],
                    $record
                );
            }
        }
    }

    private function proficiencyRecord(array $proficiency, string $category, string $classIndex): array
    {
        return [
            'category' => $category,
            'proficiency_index' => $proficiency['index'] ?? '',
            'proficiency_name' => $proficiency['name'] ?? ($proficiency['index'] ?? ''),
            'source_type' => 'class',
            'source_index' => $classIndex,
            'proficiency_level' => 1,
            'is_active' => true,
        ];
    }

    private function proficiencyCategory(string $index): string
    {
        return match (true) {
            str_starts_with($index, 'skill-') => 'skill',
            str_starts_with($index, 'weapon-') => 'weapon',
            str_starts_with($index, 'armor-') => 'armor',
            str_starts_with($index, 'tool-') => 'tool',
            str_starts_with($index, 'language-') => 'language',
            default => '',
        };
    }

    /**
     * Valida raza, subraza y trasfondo con el catálogo de D&D.
     */
    private function validateCatalogSelection(Request $request): array
    {
        $data = $request->validate([
            'race_index' => ['nullable', 'string', 'max:100'],
            'subrace_index' => ['nullable', 'string', 'max:100'],
            'background_index' => ['nullable', 'string', 'max:100'],
        ]);

        $dndApi = app(DndApiService::class);

        $raceIndex = $data['race_index'] ?? null;
        $subraceIndex = $data['subrace_index'] ?? null;
        $backgroundIndex = $data['background_index'] ?? null;

        $raceName = $request->input('race');
        $backgroundName = null;
        $race = null;
        $subrace = null;
        $background = null;

        if ($subraceIndex && ! $raceIndex) {
            throw ValidationException::withMessages([
                'subrace_index' => [
                    'Debes seleccionar una raza antes de elegir una subraza.',
                ],
            ]);
        }

        if ($raceIndex) {
            $race = $dndApi->getRace($raceIndex);

            if (! $race) {
                throw ValidationException::withMessages([
                    'race_index' => [
                        'La raza seleccionada no existe en el catálogo.',
                    ],
                ]);
            }

            $raceName = $race['name'];

            if ($subraceIndex) {
                $subrace = $dndApi->getSubrace($subraceIndex);

                if (! $subrace) {
                    throw ValidationException::withMessages([
                        'subrace_index' => [
                            'La subraza seleccionada no existe en el catálogo.',
                        ],
                    ]);
                }

                $validSubraces = collect($race['subraces'] ?? [])
                    ->pluck('index')
                    ->all();

                if (! in_array($subraceIndex, $validSubraces, true)) {
                    throw ValidationException::withMessages([
                        'subrace_index' => [
                            'La subraza seleccionada no pertenece a la raza elegida.',
                        ],
                    ]);
                }
            }
        }

        if ($backgroundIndex) {
            $background = $dndApi->getBackground($backgroundIndex);

            if (! $background) {
                throw ValidationException::withMessages([
                    'background_index' => [
                        'El trasfondo seleccionado no existe en el catálogo.',
                    ],
                ]);
            }

            $backgroundName = $background['name'];
        }

        return [
            'race_index' => $raceIndex,
            'subrace_index' => $subraceIndex,
            'background_index' => $backgroundIndex,
            'race' => $raceName,
            'background' => $backgroundName,
            'race_details' => $race ?? null,
            'subrace_details' => $subrace ?? null,
            'background_details' => $background ?? null,
        ];
    }

    /**
     * Guarda las competencias fijas concedidas por raza, subraza y trasfondo.
     * Las elecciones opcionales se gestionarán en el formulario en una fase posterior.
     */
    private function persistCatalogProficiencies(Character $character, array $data): void
    {
        $sources = [
            ['type' => 'race', 'index' => $data['race_index'] ?? null, 'details' => $data['race_details'] ?? null],
            ['type' => 'subrace', 'index' => $data['subrace_index'] ?? null, 'details' => $data['subrace_details'] ?? null],
            ['type' => 'background', 'index' => $data['background_index'] ?? null, 'details' => $data['background_details'] ?? null],
        ];

        foreach ($sources as $source) {
            if (! $source['index'] || ! is_array($source['details'])) {
                continue;
            }

            foreach (($source['details']['starting_proficiencies'] ?? []) as $proficiency) {
                $index = $proficiency['index'] ?? null;
                if (! $index) {
                    continue;
                }

                $category = $this->proficiencyCategory($index);
                if (! $category) {
                    continue;
                }

                $character->proficiencies()->firstOrCreate(
                    [
                        'category' => $category,
                        'proficiency_index' => $index,
                    ],
                    [
                        'proficiency_name' => $proficiency['name'] ?? $index,
                        'source_type' => $source['type'],
                        'source_index' => $source['index'],
                        'proficiency_level' => 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Valida las puntuaciones y bonificaciones de características.
     */
    private function validateAbilityScores(Request $request): array
    {
        $validated = $request->validate([
            'ability_score_method' => [
                'nullable',
                'in:manual,standard_array,point_buy',
            ],

            'strength' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],
            'dexterity' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],
            'constitution' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],
            'intelligence' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],
            'wisdom' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],
            'charisma' => [
                'sometimes', 'nullable', 'integer', 'min:1', 'max:20',
            ],

            'strength_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
            'dexterity_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
            'constitution_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
            'intelligence_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
            'wisdom_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
            'charisma_bonus' => [
                'sometimes', 'integer', 'min:-5', 'max:5',
            ],
        ]);

        $errors = app(AbilityScoreValidator::class)
            ->validate($validated);

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * Calcula el bonificador de competencia según el nivel.
     */
    private function calculateProficiencyBonus(int $level): int
    {
        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            default => 6,
        };
    }
}
