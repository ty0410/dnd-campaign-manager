<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        // «Mis campañas»: las que diriges. Las que solo juegas están en «Otras campañas».
        return response()->json(
            $request->user()->masteredCampaigns()->with('master')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $campaign = $user->masteredCampaigns()->create($validated);
        $campaign->users()->attach($user->id);

        return response()->json([
            'message' => 'Campaña creada correctamente.',
            'campaign' => $campaign->fresh(),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $campaign = $request->user()->campaigns()->with('master')->find($id);
        if (!$campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        if ((int) $campaign->master_id === (int) $request->user()->id) {
            $campaign->makeVisible('invite_code');
        }

        return response()->json($campaign);
    }

    /**
     * Campañas en las que juegas con algún personaje pero que no diriges.
     */
    public function joined(Request $request)
    {
        $user = $request->user();

        $campaigns = $user->campaigns()
            ->where('campaigns.master_id', '!=', $user->id)
            ->with([
                'master:id,name,username',
                'characters' => fn ($q) => $q->where('characters.user_id', $user->id),
            ])
            ->latest('campaigns.id')
            ->get();

        return response()->json($campaigns);
    }

    /**
     * Genera un código nuevo e invalida el anterior (solo el Dungeon Master).
     */
    public function regenerateInviteCode(Request $request, $id)
    {
        $campaign = $request->user()->masteredCampaigns()->find($id);

        if (!$campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        $campaign->update(['invite_code' => Campaign::generateInviteCode()]);
        $campaign->makeVisible('invite_code');

        return response()->json([
            'message' => 'Código de invitación renovado.',
            'invite_code' => $campaign->invite_code,
        ]);
    }

    public function update(Request $request, $id)
    {
        $campaign = $request->user()->masteredCampaigns()->find($id);
        if (!$campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes permisos para modificarla.',
            ], 404);
        }

        $campaign->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json([
            'message' => 'Campaña actualizada correctamente.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    public function uploadImage(Request $request, $id)
    {
        $campaign = $request->user()->masteredCampaigns()->find($id);
        if (!$campaign) {
            return response()->json(['message' => 'Campaña no encontrada o sin permisos.'], 404);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($campaign->image_path) {
            Storage::disk('public')->delete($campaign->image_path);
        }

        $campaign->image_path = $request->file('image')->store('campaigns', 'public');
        $campaign->save();

        return response()->json([
            'message' => 'Imagen de campaña actualizada correctamente.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    public function deleteImage(Request $request, $id)
    {
        $campaign = $request->user()->masteredCampaigns()->find($id);
        if (!$campaign) {
            return response()->json(['message' => 'Campaña no encontrada o sin permisos.'], 404);
        }

        if ($campaign->image_path) {
            Storage::disk('public')->delete($campaign->image_path);
            $campaign->image_path = null;
            $campaign->save();
        }

        return response()->json([
            'message' => 'Imagen eliminada correctamente.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $campaign = $request->user()->masteredCampaigns()->find($id);
        if (!$campaign) {
            return response()->json([
                'message' => 'Campaña no encontrada o no tienes permisos para eliminarla.',
            ], 404);
        }

        if ($campaign->image_path) {
            Storage::disk('public')->delete($campaign->image_path);
        }
        $campaign->delete();

        return response()->json(['message' => 'Campaña eliminada correctamente.']);
    }
}
