<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterClass;
use Illuminate\Http\Request;

class CharacterClassController extends Controller
{
    public function index(Request $request, $characterId)
    {
        $character = Character::find($characterId);

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

        $classes = $character->characterClasses()->get();

        return response()->json($classes);
    }

    public function store(Request $request, $characterId)
    {
        $character = Character::find($characterId);

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
            'class' => ['required', 'string', 'max:100'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $characterClass = $character->characterClasses()->create([
            'class' => $validated['class'],
            'level' => $validated['level'],
        ]);

        return response()->json([
            'message' => 'Clase añadida correctamente.',
            'character_class' => $characterClass,
        ], 201);
    }

    public function show(Request $request, $characterId, $classId)
    {
        $character = Character::find($characterId);

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

        $characterClass = $character->characterClasses()
            ->where('id', $classId)
            ->first();

        if (!$characterClass) {
            return response()->json([
                'message' => 'Clase no encontrada.',
            ], 404);
        }

        return response()->json($characterClass);
    }

    public function update(Request $request, $characterId, $classId)
    {
        $character = Character::find($characterId);

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

        $characterClass = $character->characterClasses()
            ->where('id', $classId)
            ->first();

        if (!$characterClass) {
            return response()->json([
                'message' => 'Clase no encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'class' => ['required', 'string', 'max:100'],
            'level' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $characterClass->update([
            'class' => $validated['class'],
            'level' => $validated['level'],
        ]);

        return response()->json([
            'message' => 'Clase actualizada correctamente.',
            'character_class' => $characterClass,
        ]);
    }

    public function destroy(Request $request, $characterId, $classId)
    {
        $character = Character::find($characterId);

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

        $characterClass = $character->characterClasses()
            ->where('id', $classId)
            ->first();

        if (!$characterClass) {
            return response()->json([
                'message' => 'Clase no encontrada.',
            ], 404);
        }

        $characterClass->delete();

        return response()->json([
            'message' => 'Clase eliminada correctamente.',
        ]);
    }
}