<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterAttack;
use Illuminate\Http\Request;

/**
 * Ataques y conjuros de daño del personaje (solo el propietario).
 * Los bonificadores se calculan en la ficha a partir de las características.
 */
class CharacterAttackController extends Controller
{
    private function rules(): array
    {
        $abilities = 'in:strength,dexterity,constitution,intelligence,wisdom,charisma';

        return [
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:attack,save'],
            'ability' => ['required', $abilities],
            'add_proficiency' => ['sometimes', 'boolean'],
            'save_ability' => ['nullable', 'required_if:kind,save', $abilities],
            'bonus' => ['sometimes', 'integer', 'min:-20', 'max:30'],
            'damage_dice' => ['nullable', 'string', 'regex:/^\d{1,2}d(4|6|8|10|12|20|100)$/'],
            'damage_bonus' => ['sometimes', 'integer', 'min:-20', 'max:50'],
            'damage_add_ability' => ['sometimes', 'boolean'],
            'damage_type' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function ownCharacter(Request $request, $id): ?Character
    {
        return Character::where('user_id', $request->user()->id)->find($id);
    }

    public function store(Request $request, $characterId)
    {
        $character = $this->ownCharacter($request, $characterId);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate($this->rules());

        if ($validated['kind'] === 'attack') {
            $validated['save_ability'] = null;
        }

        $attack = $character->attacks()->create($validated + [
            'sort_order' => ((int) $character->attacks()->max('sort_order')) + 1,
        ]);

        return response()->json(['message' => 'Ataque añadido.', 'attack' => $attack], 201);
    }

    public function update(Request $request, $characterId, $attackId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $attack = $character ? CharacterAttack::where('character_id', $character->id)->find($attackId) : null;

        if (! $attack) {
            return response()->json(['message' => 'Ataque no encontrado.'], 404);
        }

        $validated = $request->validate($this->rules());

        if ($validated['kind'] === 'attack') {
            $validated['save_ability'] = null;
        }

        $attack->update($validated);

        return response()->json(['message' => 'Ataque actualizado.', 'attack' => $attack]);
    }

    public function destroy(Request $request, $characterId, $attackId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $attack = $character ? CharacterAttack::where('character_id', $character->id)->find($attackId) : null;

        if (! $attack) {
            return response()->json(['message' => 'Ataque no encontrado.'], 404);
        }

        $attack->delete();

        return response()->json(['message' => 'Ataque eliminado.']);
    }
}
