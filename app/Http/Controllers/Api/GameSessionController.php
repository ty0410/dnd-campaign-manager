<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\Request;

class GameSessionController extends Controller
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

        $sessions = $campaign->gameSessions()
            ->orderBy('session_date', 'desc')
            ->get();

        return response()->json($sessions);
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
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = $campaign->gameSessions()->create([
            'title' => $validated['title'],
            'session_date' => $validated['session_date'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Sesión de juego creada correctamente.',
            'session' => $session,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $session = GameSession::with([
            'campaign',
            'encounters',
        ])->find($id);

        if (!$session) {
            return response()->json([
                'message' => 'Sesión de juego no encontrada.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $session->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a esta sesión de juego.',
            ], 404);
        }

        return response()->json($session);
    }

    public function update(Request $request, $id)
    {
        $session = GameSession::find($id);

        if (!$session) {
            return response()->json([
                'message' => 'Sesión de juego no encontrada.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $session->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a esta sesión de juego.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $session->update([
            'title' => $validated['title'],
            'session_date' => $validated['session_date'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Sesión de juego actualizada correctamente.',
            'session' => $session,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $session = GameSession::find($id);

        if (!$session) {
            return response()->json([
                'message' => 'Sesión de juego no encontrada.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $session->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a esta sesión de juego.',
            ], 404);
        }

        $session->delete();

        return response()->json([
            'message' => 'Sesión de juego eliminada correctamente.',
        ]);
    }
}
