<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CharacterClassController;
use App\Http\Controllers\Api\NpcController;
use App\Http\Controllers\Api\LocationController;

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');
// Rutas para campañas

Route::get('/campaigns', [CampaignController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns', [CampaignController::class, 'store'])
    ->middleware('auth:sanctum');

    Route::get('/campaigns/{id}', [CampaignController::class, 'show'])
    ->middleware('auth:sanctum');

    Route::put('/campaigns/{id}', [CampaignController::class, 'update'])
    ->middleware('auth:sanctum');

    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])
    ->middleware('auth:sanctum');

// Rutas para personajes
    Route::get('/campaigns/{campaignId}/characters', [CharacterController::class, 'index'])
    ->middleware('auth:sanctum');
    Route::post('/campaigns/{campaignId}/characters', [CharacterController::class, 'store'])
    ->middleware('auth:sanctum');
    Route::get('/characters/{id}', [CharacterController::class, 'show'])
    ->middleware('auth:sanctum');
    Route::put('/characters/{id}', [CharacterController::class, 'update'])
    ->middleware('auth:sanctum');
    Route::delete('/characters/{id}', [CharacterController::class, 'destroy'])
    ->middleware('auth:sanctum');

    // Rutas para clases de personajes
    Route::get('/characters/{characterId}/classes', [CharacterClassController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('/characters/{characterId}/classes', [CharacterClassController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/characters/{characterId}/classes/{classId}', [CharacterClassController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/characters/{characterId}/classes/{classId}', [CharacterClassController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/characters/{characterId}/classes/{classId}', [CharacterClassController::class, 'destroy'])
    ->middleware('auth:sanctum');

    // Rutas para NPCs
    
Route::get('/campaigns/{campaignId}/npcs', [NpcController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns/{campaignId}/npcs', [NpcController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/npcs/{id}', [NpcController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/npcs/{id}', [NpcController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/npcs/{id}', [NpcController::class, 'destroy'])
    ->middleware('auth:sanctum');


    // Rutas para lugares
Route::get('/campaigns/{campaignId}/locations', [LocationController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns/{campaignId}/locations', [LocationController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/locations/{id}', [LocationController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/locations/{id}', [LocationController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/locations/{id}', [LocationController::class, 'destroy'])
    ->middleware('auth:sanctum');