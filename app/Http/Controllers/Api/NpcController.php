<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Npc;
use Illuminate\Http\Request;

class NpcController extends Controller
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

        $npcs = $campaign->npcs()->get();

        return response()->json($npcs);
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
            'role' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $npc = $campaign->npcs()->create([
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'role' => $validated['role'] ?? null,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return response()->json([
            'message' => 'NPC creado correctamente.',
            'npc' => $npc,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $npc = Npc::with('campaign')->find($id);

        if (!$npc) {
            return response()->json([
                'message' => 'NPC no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $npc->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este NPC.',
            ], 404);
        }

        return response()->json($npc);
    }

    public function update(Request $request, $id)
    {
        $npc = Npc::find($id);

        if (!$npc) {
            return response()->json([
                'message' => 'NPC no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $npc->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este NPC.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $npc->update([
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'role' => $validated['role'] ?? null,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return response()->json([
            'message' => 'NPC actualizado correctamente.',
            'npc' => $npc,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $npc = Npc::find($id);

        if (!$npc) {
            return response()->json([
                'message' => 'NPC no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $npc->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este NPC.',
            ], 404);
        }

        $npc->delete();

        return response()->json([
            'message' => 'NPC eliminado correctamente.',
        ]);
    }
}