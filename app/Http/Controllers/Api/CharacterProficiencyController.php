<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterProficiency;
use App\Models\CharacterProficiencyOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Edición manual de competencias desde la ficha (solo el propietario):
 *  - nivel de competencia en habilidades (0, 1, 2 = pericia) y salvaciones (0, 1);
 *  - altas y bajas de otras competencias (armaduras, armas, herramientas, idiomas).
 */
class CharacterProficiencyController extends Controller
{
    private const SKILLS = [
        'acrobatics', 'animal-handling', 'arcana', 'athletics', 'deception', 'history', 'insight',
        'intimidation', 'investigation', 'medicine', 'nature', 'perception', 'performance',
        'persuasion', 'religion', 'sleight-of-hand', 'stealth', 'survival',
    ];

    private const ABILITIES = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];

    private function ownCharacter(Request $request, $id): ?Character
    {
        return Character::where('user_id', $request->user()->id)->find($id);
    }

    public function setLevel(Request $request, $characterId)
    {
        $character = $this->ownCharacter($request, $characterId);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate([
            'category' => ['required', 'in:skill,saving_throw'],
            'index' => ['required', 'string', 'max:60'],
            'level' => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        $valid = $validated['category'] === 'skill' ? self::SKILLS : self::ABILITIES;

        if (! in_array($validated['index'], $valid, true)) {
            return response()->json(['message' => 'Elemento no válido.'], 422);
        }

        CharacterProficiencyOverride::updateOrCreate(
            [
                'character_id' => $character->id,
                'category' => $validated['category'],
                'proficiency_index' => $validated['index'],
            ],
            [
                'proficiency_name' => $validated['index'],
                'proficiency_level' => $validated['level'],
            ]
        );

        return response()->json(['message' => 'Competencia actualizada.']);
    }

    public function store(Request $request, $characterId)
    {
        $character = $this->ownCharacter($request, $characterId);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate([
            'category' => ['required', 'in:armor,weapon,tool,language'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $index = 'manual-' . Str::slug($validated['name']);

        $exists = $character->proficiencies()
            ->where('category', $validated['category'])
            ->where('proficiency_index', $index)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya tienes esa competencia.'], 422);
        }

        $proficiency = CharacterProficiency::create([
            'character_id' => $character->id,
            'category' => $validated['category'],
            'proficiency_index' => $index,
            'proficiency_name' => trim($validated['name']),
            'source_type' => 'manual',
            'source_index' => null,
            'proficiency_level' => 1,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Competencia añadida.', 'proficiency' => $proficiency], 201);
    }

    public function destroy(Request $request, $characterId, $proficiencyId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $proficiency = $character ? $character->proficiencies()->find($proficiencyId) : null;

        if (! $proficiency) {
            return response()->json(['message' => 'Competencia no encontrada.'], 404);
        }

        $proficiency->delete();

        return response()->json(['message' => 'Competencia eliminada.']);
    }
}
