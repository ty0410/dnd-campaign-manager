<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
{
    $campaigns = $request->user()
        ->campaigns()
        ->with('master')
        ->get();

    return response()->json($campaigns);
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
        'campaign' => $campaign,
    ], 201);
}
}
