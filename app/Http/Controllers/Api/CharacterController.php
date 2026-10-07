<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function index(Request $request, $campaignId)
    {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $characters = $campaign->characters()
            ->with('user')
            ->with('characterClasses')
            ->get();

        return response()->json($characters);
    }

    public function store(Request $request, $campaignId)
    {
        $campaign = $request->user()
            ->campaigns()
            ->find($campaignId);

        if (!$campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes acceso a ella.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        $proficiencyBonus = $this->calculateProficiencyBonus(
            $validated['level']
        );

        $character = $campaign->characters()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'level' => $validated['level'],
            'proficiency_bonus' => $proficiencyBonus,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Personaje creado correctamente.',
            'character' => $character,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $character = Character::with([
            'user',
            'characterClasses',
            'campaign',
        ])->find($id);

        if (!$character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $character->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este personaje.',
            ], 404);
        }

        return response()->json($character);
    }

    public function update(Request $request, $id)
    {
        $character = Character::find($id);

        if (!$character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $character->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este personaje.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:100'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        $proficiencyBonus = $this->calculateProficiencyBonus(
            $validated['level']
        );

        $character->update([
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'level' => $validated['level'],
            'proficiency_bonus' => $proficiencyBonus,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Personaje actualizado correctamente.',
            'character' => $character,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $character = Character::find($id);

        if (!$character) {
            return response()->json([
                'message' => 'Personaje no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $character->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este personaje.',
            ], 404);
        }

        $character->delete();

        return response()->json([
            'message' => 'Personaje eliminado correctamente.',
        ]);
    }

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