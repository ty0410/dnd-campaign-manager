<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
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
        'race' => ['nullable', 'string', 'max:100'],
        'level' => ['nullable', 'integer', 'min:1', 'max:20'],
        'description' => ['nullable', 'string'],
    ]);

    $character = $campaign->characters()->create([
        'user_id' => $request->user()->id,
        'name' => $validated['name'],
        'race' => $validated['race'] ?? null,
        'level' => $validated['level'] ?? 1,
        'description' => $validated['description'] ?? null,
    ]);

    return response()->json([
        'message' => 'Personaje creado correctamente.',
        'character' => $character,
    ], 201);
}
}