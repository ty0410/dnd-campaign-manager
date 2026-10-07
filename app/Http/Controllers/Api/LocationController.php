<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
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

        $locations = $campaign->locations()
            ->with('parent')
            ->with('children')
            ->get();

        return response()->json($locations);
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
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Location::where('id', $validated['parent_id'])
                ->where('campaign_id', $campaign->id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'message' => 'El lugar padre no existe en esta campaña.',
                ], 422);
            }
        }

        $location = $campaign->locations()->create([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lugar creado correctamente.',
            'location' => $location,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $location = Location::with([
            'campaign',
            'parent',
            'children',
        ])->find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $location->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este lugar.',
            ], 404);
        }

        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $location->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este lugar.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Location::where('id', $validated['parent_id'])
                ->where('campaign_id', $location->campaign_id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'message' => 'El lugar padre no existe en esta campaña.',
                ], 422);
            }

            if ($parent->id == $location->id) {
                return response()->json([
                    'message' => 'Un lugar no puede ser su propio padre.',
                ], 422);
            }
        }

        $location->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lugar actualizado correctamente.',
            'location' => $location,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        $hasAccess = $request->user()
            ->campaigns()
            ->where('campaigns.id', $location->campaign_id)
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'No tienes acceso a este lugar.',
            ], 404);
        }

        $location->delete();

        return response()->json([
            'message' => 'Lugar eliminado correctamente.',
        ]);
    }
}