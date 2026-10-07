<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Models\GameSession;
use Illuminate\Http\Request;

class EncounterController extends Controller
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

        $encounters = $campaign->encounters()
            ->with('gameSession')
            ->with('combatants')
            ->get();

        return response()->json($encounters);
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
            'game_session_id' => ['nullable', 'integer', 'exists:game_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:combat,social,exploration,puzzle'],
            'status' => ['nullable', 'string', 'in:planned,active,completed'],
        ]);

        if (!empty($validated['game_session_id'])) {
            $session = GameSession::where('id', $validated['game_session_id'])
                ->where('campaign_id', $campaign->id)
                ->first();

            if (!$session) {
                return response()->json([
                    'message' => 'La sesión de juego no pertenece a esta campaña.',
                ], 422);
            }
        }

        $encounter = $campaign->encounters()->create([
            'game_session_id' => $validated['game_session_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'combat',
            'status' => $validated['status'] ?? 'planned',
        ]);

        return response()->json([
            'message' => 'Encuentro creado correctamente.',
            'encounter' => $encounter,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $encounter = Encounter::with([
            'campaign',
            'gameSession',
            'combatants',
        ])->find($id);

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

        return response()->json($encounter);
    }

    public function update(Request $request, $id)
    {
        $encounter = Encounter::find($id);

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
            'game_session_id' => ['nullable', 'integer', 'exists:game_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'in:combat,social,exploration,puzzle'],
            'status' => ['nullable', 'string', 'in:planned,active,completed'],
        ]);

        if (!empty($validated['game_session_id'])) {
            $session = GameSession::where('id', $validated['game_session_id'])
                ->where('campaign_id', $encounter->campaign_id)
                ->first();

            if (!$session) {
                return response()->json([
                    'message' => 'La sesión de juego no pertenece a esta campaña.',
                ], 422);
            }
        }

        $encounter->update([
            'game_session_id' => $validated['game_session_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'combat',
            'status' => $validated['status'] ?? 'planned',
        ]);

        return response()->json([
            'message' => 'Encuentro actualizado correctamente.',
            'encounter' => $encounter,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $encounter = Encounter::find($id);

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

        $encounter->delete();

        return response()->json([
            'message' => 'Encuentro eliminado correctamente.',
        ]);
    }
}
