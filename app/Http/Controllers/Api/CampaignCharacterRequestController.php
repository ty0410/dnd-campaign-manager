<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignCharacterRequest;
use App\Models\Character;
use Illuminate\Http\Request;

/**
 * Incorporar un personaje a una campaña exige la aprobación de las dos
 * partes: el jugador (dueño del personaje) y el Dungeon Master.
 *
 *  - El jugador pide entrar con el código de la campaña → aprueba el DM.
 *  - El DM invita a un personaje de otro jugador → aprueba su dueño.
 */
class CampaignCharacterRequestController extends Controller
{
    /**
     * Invitaciones recibidas y peticiones enviadas por el usuario.
     */
    public function mine(Request $request)
    {
        $user = $request->user();

        $invitations = CampaignCharacterRequest::query()
            ->where('status', CampaignCharacterRequest::PENDING)
            ->where('direction', CampaignCharacterRequest::DM_INVITATION)
            ->whereHas('character', fn ($q) => $q->where('user_id', $user->id))
            ->with(['campaign.master:id,name', 'character:id,name,race,level,user_id'])
            ->latest()
            ->get();

        $requests = CampaignCharacterRequest::query()
            ->where('status', CampaignCharacterRequest::PENDING)
            ->where('direction', CampaignCharacterRequest::PLAYER_REQUEST)
            ->where('requested_by', $user->id)
            ->with(['campaign.master:id,name', 'character:id,name,race,level,user_id'])
            ->latest()
            ->get();

        return response()->json([
            'invitations' => $invitations,
            'requests' => $requests,
        ]);
    }

    /**
     * Solicitudes pendientes de una campaña (solo su Dungeon Master).
     */
    public function forCampaign(Request $request, $campaignId)
    {
        $campaign = $request->user()->masteredCampaigns()->find($campaignId);

        if (! $campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        $pending = CampaignCharacterRequest::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignCharacterRequest::PENDING)
            ->with(['character.user:id,name', 'requester:id,name'])
            ->latest()
            ->get();

        return response()->json($pending);
    }

    /**
     * El jugador pide incorporar uno de sus personajes con el código.
     */
    public function join(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'character_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $character = Character::where('user_id', $user->id)->find($validated['character_id']);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $code = strtoupper(trim($validated['code']));
        $campaign = Campaign::where('invite_code', $code)->first();

        if (! $campaign) {
            return response()->json(['message' => 'El código no corresponde a ninguna campaña.'], 404);
        }

        if ($character->campaigns()->whereKey($campaign->id)->exists()) {
            return response()->json(['message' => 'Ese personaje ya está en la campaña.'], 422);
        }

        if ($this->hasPending($campaign->id, $character->id)) {
            return response()->json(['message' => 'Ya hay una solicitud pendiente para este personaje en esa campaña.'], 422);
        }

        // Si quien pide es el propio Dungeon Master, no hay segunda parte que aprobar.
        if ((int) $campaign->master_id === (int) $user->id) {
            CampaignCharacterRequest::attachToCampaign($campaign, $character);

            return response()->json([
                'status' => 'attached',
                'message' => 'Personaje añadido a tu campaña.',
            ]);
        }

        CampaignCharacterRequest::create([
            'campaign_id' => $campaign->id,
            'character_id' => $character->id,
            'requested_by' => $user->id,
            'direction' => CampaignCharacterRequest::PLAYER_REQUEST,
            'status' => CampaignCharacterRequest::PENDING,
        ]);

        return response()->json([
            'status' => 'pending',
            'message' => "Solicitud enviada. El Dungeon Master de «{$campaign->name}» debe aprobarla.",
        ], 201);
    }

    public function approve(Request $request, $id)
    {
        $entry = $this->findPendingFor($request, $id);

        if (! $entry) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        // Puede haberse añadido por otra vía mientras tanto.
        $entry->approve($request->user());

        return response()->json(['message' => 'Solicitud aprobada. El personaje ya está en la campaña.']);
    }

    public function reject(Request $request, $id)
    {
        $entry = $this->findPendingFor($request, $id);

        if (! $entry) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $entry->reject($request->user());

        return response()->json(['message' => 'Solicitud rechazada.']);
    }

    /**
     * Quien inició la solicitud puede retirarla mientras esté pendiente.
     */
    public function cancel(Request $request, $id)
    {
        $entry = CampaignCharacterRequest::where('status', CampaignCharacterRequest::PENDING)
            ->where('requested_by', $request->user()->id)
            ->find($id);

        if (! $entry) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $entry->delete();

        return response()->json(['message' => 'Solicitud cancelada.']);
    }

    /**
     * Solicitud pendiente que le toca responder al usuario autenticado.
     */
    private function findPendingFor(Request $request, $id): ?CampaignCharacterRequest
    {
        $entry = CampaignCharacterRequest::with(['campaign', 'character'])
            ->where('status', CampaignCharacterRequest::PENDING)
            ->find($id);

        if (! $entry || $entry->approverId() !== (int) $request->user()->id) {
            return null;
        }

        return $entry;
    }

    private function hasPending($campaignId, $characterId): bool
    {
        return CampaignCharacterRequest::where('campaign_id', $campaignId)
            ->where('character_id', $characterId)
            ->where('status', CampaignCharacterRequest::PENDING)
            ->exists();
    }
}
