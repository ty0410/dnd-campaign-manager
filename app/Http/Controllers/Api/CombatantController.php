<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combatant;
use App\Models\Encounter;
use App\Models\Character;
use Illuminate\Http\Request;

class CombatantController extends Controller
{
    /**
     * Listar los participantes de un encuentro.
     */
    public function index(Request $request, $encounterId)
    {
        $encounter = Encounter::find($encounterId);

        if (!$encounter) {
            return response()->json([
                'message' => 'Encuentro no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $encounter->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este encuentro.',
            ], 404);
        }

        $combatants = $encounter->combatants()
            ->with('character')
            ->get();

        return response()->json($combatants);
    }

    /**
     * Crear un participante en un encuentro.
     */
    public function store(Request $request, $encounterId)
    {
        $encounter = Encounter::find($encounterId);

        if (!$encounter) {
            return response()->json([
                'message' => 'Encuentro no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $encounter->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este encuentro.',
            ], 404);
        }

        $validated = $request->validate([
            'character_id' => ['nullable', 'integer', 'exists:characters,id'],
            'monster_index' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'initiative' => ['nullable', 'integer', 'min:0', 'max:999'],
            'hit_points' => ['nullable', 'integer', 'min:0'],
            'max_hit_points' => ['nullable', 'integer', 'min:0'],
            'is_player' => ['boolean'],
            'is_defeated' => ['boolean'],
        ]);

        /*
         * Si se proporciona un personaje, comprobamos que
         * pertenece a la misma campaña que el encuentro.
         */
        if (!empty($validated['character_id'])) {

            $character = Character::where('id', $validated['character_id'])
                ->where('campaign_id', $encounter->campaign_id)
                ->first();

            if (!$character) {
                return response()->json([
                    'message' => 'El personaje no pertenece a la campaña del encuentro.',
                ], 422);
            }
        }

        /*
         * Un participante debe ser un personaje o un monstruo.
         */
        if (
            empty($validated['character_id']) &&
            empty($validated['monster_index'])
        ) {
            return response()->json([
                'message' => 'Debes indicar un personaje o un monstruo.',
            ], 422);
        }

        $combatant = $encounter->combatants()->create([
            'character_id' => $validated['character_id'] ?? null,
            'monster_index' => $validated['monster_index'] ?? null,
            'name' => $validated['name'],
            'initiative' => $validated['initiative'] ?? null,
            'hit_points' => $validated['hit_points'] ?? null,
            'max_hit_points' => $validated['max_hit_points'] ?? null,
            'is_player' => $validated['is_player'] ?? false,
            'is_defeated' => $validated['is_defeated'] ?? false,
        ]);

        return response()->json([
            'message' => 'Participante añadido correctamente.',
            'combatant' => $combatant,
        ], 201);
    }

    /**
     * Consultar un participante concreto.
     */
    public function show(Request $request, $id)
    {
        $combatant = Combatant::with([
            'encounter',
            'character',
        ])->find($id);

        if (!$combatant) {
            return response()->json([
                'message' => 'Participante no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $combatant->encounter->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este participante.',
            ], 404);
        }

        return response()->json($combatant);
    }

    /**
     * Actualizar un participante.
     */
    public function update(Request $request, $id)
    {
        $combatant = Combatant::with('encounter')->find($id);

        if (!$combatant) {
            return response()->json([
                'message' => 'Participante no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $combatant->encounter->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este participante.',
            ], 404);
        }

        $validated = $request->validate([
            'character_id' => ['nullable', 'integer', 'exists:characters,id'],
            'monster_index' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'initiative' => ['nullable', 'integer', 'min:0', 'max:999'],
            'hit_points' => ['nullable', 'integer', 'min:0'],
            'max_hit_points' => ['nullable', 'integer', 'min:0'],
            'is_player' => ['boolean'],
            'is_defeated' => ['boolean'],
        ]);

        /*
         * Comprobar que el personaje pertenece a la campaña
         * del encuentro.
         */
        if (!empty($validated['character_id'])) {

            $character = Character::where('id', $validated['character_id'])
                ->where('campaign_id', $combatant->encounter->campaign_id)
                ->first();

            if (!$character) {
                return response()->json([
                    'message' => 'El personaje no pertenece a la campaña del encuentro.',
                ], 422);
            }
        }

        /*
         * Un participante debe ser un personaje o un monstruo.
         */
        if (
            empty($validated['character_id']) &&
            empty($validated['monster_index'])
        ) {
            return response()->json([
                'message' => 'Debes indicar un personaje o un monstruo.',
            ], 422);
        }

        $combatant->update([
            'character_id' => $validated['character_id'] ?? null,
            'monster_index' => $validated['monster_index'] ?? null,
            'name' => $validated['name'],
            'initiative' => $validated['initiative'] ?? null,
            'hit_points' => $validated['hit_points'] ?? null,
            'max_hit_points' => $validated['max_hit_points'] ?? null,
            'is_player' => $validated['is_player'] ?? false,
            'is_defeated' => $validated['is_defeated'] ?? false,
        ]);

        return response()->json([
            'message' => 'Participante actualizado correctamente.',
            'combatant' => $combatant,
        ]);
    }

    /**
     * Eliminar un participante.
     */
    public function destroy(Request $request, $id)
    {
        $combatant = Combatant::with('encounter')->find($id);

        if (!$combatant) {
            return response()->json([
                'message' => 'Participante no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $combatant->encounter->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este participante.',
            ], 404);
        }

        $combatant->delete();

        return response()->json([
            'message' => 'Participante eliminado correctamente.',
        ]);
    }
}