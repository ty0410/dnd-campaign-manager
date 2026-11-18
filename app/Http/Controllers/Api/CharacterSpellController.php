<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterSpell;
use App\Services\DndApiService;
use Illuminate\Http\Request;

/**
 * Conjuros del personaje gestionados desde la ficha (solo el propietario).
 * Se puede añadir un conjuro de la lista de cualquier clase lanzadora,
 * aunque el personaje no tenga esa clase.
 */
class CharacterSpellController extends Controller
{
    private function ownCharacter(Request $request, $id): ?Character
    {
        return Character::where('user_id', $request->user()->id)->find($id);
    }

    /** Añade uno o varios conjuros de una vez (spell_indexes) desde cualquier lista. */
    public function store(Request $request, $characterId, DndApiService $api)
    {
        $character = $this->ownCharacter($request, $characterId);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate([
            'spell_indexes' => ['required', 'array', 'min:1', 'max:60'],
            'spell_indexes.*' => ['string', 'max:100'],
            // Lista de clase de la que procede (opcional, solo informativa).
            'list_class' => ['nullable', 'in:bard,cleric,druid,paladin,ranger,sorcerer,warlock,wizard'],
        ]);

        $already = $character->spells()->pluck('spell_index')->all();
        $wanted = collect($validated['spell_indexes'])->unique()->diff($already)->values()->all();

        if (! $wanted) {
            return response()->json(['message' => 'Ya tienes esos conjuros.'], 422);
        }

        try {
            $details = collect($api->getSpellDetailsForIndexes($wanted))->keyBy('index');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo consultar el catálogo. Inténtalo de nuevo.'], 502);
        }

        $added = 0;

        foreach ($wanted as $index) {
            $spell = $details->get($index);

            if (! $spell) {
                continue;
            }

            $character->spells()->create([
                'spell_index' => $spell['index'],
                'spell_name' => $spell['name'],
                'spell_level' => (int) ($spell['level'] ?? 0),
                'spell_type' => 'known',
                'source_class_index' => $validated['list_class'] ?? 'manual',
                'is_known' => true,
                'is_prepared' => false,
                // Añadido a mano: no cuenta para los límites de la clase.
                'is_exception' => true,
            ]);

            $added++;
        }

        return response()->json([
            'message' => $added === 1 ? 'Conjuro añadido.' : "{$added} conjuros añadidos.",
            'added' => $added,
        ], 201);
    }

    public function update(Request $request, $characterId, $spellId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $spell = $character ? $character->spells()->find($spellId) : null;

        if (! $spell) {
            return response()->json(['message' => 'Conjuro no encontrado.'], 404);
        }

        $validated = $request->validate(['is_prepared' => ['required', 'boolean']]);

        $spell->update($validated);

        return response()->json(['message' => 'Conjuro actualizado.', 'spell' => $spell]);
    }

    public function destroy(Request $request, $characterId, $spellId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $spell = $character ? $character->spells()->find($spellId) : null;

        if (! $spell) {
            return response()->json(['message' => 'Conjuro no encontrado.'], 404);
        }

        $spell->delete();

        return response()->json(['message' => 'Conjuro eliminado.']);
    }
}
