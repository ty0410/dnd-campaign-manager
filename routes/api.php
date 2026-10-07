<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CharacterClassController;
use App\Http\Controllers\Api\NpcController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\CombatantController;
use App\Http\Controllers\Api\DndMonsterController;
use App\Http\Controllers\Api\DndSpellController;
use App\Http\Controllers\Api\DndClassController;

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

// Rutas para sesiones de juego
Route::get('/campaigns/{campaignId}/sessions', [GameSessionController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns/{campaignId}/sessions', [GameSessionController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/sessions/{id}', [GameSessionController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/sessions/{id}', [GameSessionController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/sessions/{id}', [GameSessionController::class, 'destroy'])
    ->middleware('auth:sanctum');

// Rutas para encuentros
Route::get('/campaigns/{campaignId}/encounters', [EncounterController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns/{campaignId}/encounters', [EncounterController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/encounters/{id}', [EncounterController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/encounters/{id}', [EncounterController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/encounters/{id}', [EncounterController::class, 'destroy'])
    ->middleware('auth:sanctum');

// Rutas para combatientes
Route::get('/encounters/{encounterId}/combatants', [CombatantController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/encounters/{encounterId}/combatants', [CombatantController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/combatants/{id}', [CombatantController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/combatants/{id}', [CombatantController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/combatants/{id}', [CombatantController::class, 'destroy'])
    ->middleware('auth:sanctum');

// Rutas para la API de monstruos
Route::get('/dnd/monsters', [DndMonsterController::class, 'index']);
Route::get('/dnd/monsters/{index}', [DndMonsterController::class, 'show']);

// Rutas para la API de conjuros
Route::get('/dnd/spells', [DndSpellController::class, 'index']);
Route::get('/dnd/spells/{index}', [DndSpellController::class, 'show']);

// Rutas para la API de clases
Route::get('/dnd/classes', [DndClassController::class, 'index']);
Route::get('/dnd/classes/{index}', [DndClassController::class, 'show']);
