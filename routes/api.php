<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CharacterAttackController;
use App\Http\Controllers\Api\CharacterInventoryController;
use App\Http\Controllers\Api\CharacterProficiencyController;
use App\Http\Controllers\Api\NpcDetailController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CampaignCharacterRequestController;
use App\Http\Controllers\Api\CharacterClassController;
use App\Http\Controllers\Api\CharacterMagicController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\CombatantController;
use App\Http\Controllers\Api\DndCatalogController;
use App\Http\Controllers\Api\DndClassController;
use App\Http\Controllers\Api\DndEquipmentController;
use App\Http\Controllers\Api\DndMonsterController;
use App\Http\Controllers\Api\DndSpellController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NpcController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');
// Rutas para campañas

Route::get('/campaigns/joined', [CampaignController::class, 'joined'])
    ->middleware('auth:sanctum');
Route::get('/campaigns', [CampaignController::class, 'index'])
    ->middleware('auth:sanctum');

Route::post('/campaigns', [CampaignController::class, 'store'])
    ->middleware('auth:sanctum');

Route::get('/campaigns/{id}', [CampaignController::class, 'show'])
    ->middleware('auth:sanctum');

Route::put('/campaigns/{id}', [CampaignController::class, 'update'])
    ->middleware('auth:sanctum');

Route::post('/campaigns/{id}/image', [CampaignController::class, 'uploadImage'])
    ->middleware('auth:sanctum');

Route::delete('/campaigns/{id}/image', [CampaignController::class, 'deleteImage'])
    ->middleware('auth:sanctum');

Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])
    ->middleware('auth:sanctum');

// Rutas para personajes
Route::get('/campaigns/{campaignId}/characters', [CharacterController::class, 'index'])
    ->middleware('auth:sanctum');
Route::post('/campaigns/{campaignId}/characters', [CharacterController::class, 'store'])
    ->middleware('auth:sanctum');
Route::get('/campaigns/{campaignId}/available-characters', [CharacterController::class, 'availableForCampaign'])
    ->middleware(['auth:sanctum', 'throttle:30,1']);
// Administración (solo cuentas de administrador)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
    Route::post('/users/{id}/reactivate', [AdminController::class, 'reactivateUser']);

    Route::get('/characters', [AdminController::class, 'characters']);
    Route::patch('/characters/{id}', [AdminController::class, 'updateCharacter']);
    Route::delete('/characters/{id}', [AdminController::class, 'destroyCharacter']);

    Route::get('/campaigns', [AdminController::class, 'campaigns']);
    Route::get('/campaigns/{id}', [AdminController::class, 'showCampaign']);
    Route::patch('/campaigns/{id}', [AdminController::class, 'updateCampaign']);
    Route::delete('/campaigns/{id}', [AdminController::class, 'destroyCampaign']);

    Route::get('/admins', [AdminController::class, 'admins']);
    Route::post('/admins', [AdminController::class, 'storeAdmin']);
});

// Inventario del personaje
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/characters/{characterId}/inventory', [CharacterInventoryController::class, 'store']);
    Route::patch('/characters/{characterId}/inventory/{itemId}', [CharacterInventoryController::class, 'update']);
    Route::delete('/characters/{characterId}/inventory/{itemId}', [CharacterInventoryController::class, 'destroy']);

    // Competencias editables y ataques
    Route::put('/characters/{characterId}/proficiency-level', [CharacterProficiencyController::class, 'setLevel']);
    Route::post('/characters/{characterId}/proficiencies', [CharacterProficiencyController::class, 'store']);
    Route::delete('/characters/{characterId}/proficiencies/{proficiencyId}', [CharacterProficiencyController::class, 'destroy']);
    Route::post('/characters/{characterId}/attacks', [CharacterAttackController::class, 'store']);
    Route::put('/characters/{characterId}/attacks/{attackId}', [CharacterAttackController::class, 'update']);
    Route::delete('/characters/{characterId}/attacks/{attackId}', [CharacterAttackController::class, 'destroy']);

    // Ficha completa de PNJ: foto, características y apuntes
    Route::get('/npcs/{id}/detail', [NpcDetailController::class, 'show']);
    Route::post('/npcs/{id}/image', [NpcDetailController::class, 'uploadImage']);
    Route::delete('/npcs/{id}/image', [NpcDetailController::class, 'deleteImage']);
    Route::put('/npcs/{id}/note', [NpcDetailController::class, 'saveNote']);
    Route::post('/npcs/{id}/attributes', [NpcDetailController::class, 'storeAttribute']);
    Route::patch('/npc-attributes/{id}', [NpcDetailController::class, 'updateAttribute']);
    Route::delete('/npc-attributes/{id}', [NpcDetailController::class, 'destroyAttribute']);
});

// Perfil público y amistades
Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::get('/users/{username}', [ProfileController::class, 'show']);

    Route::get('/friends', [FriendshipController::class, 'index']);
    Route::get('/friends/search', [FriendshipController::class, 'search'])->middleware('throttle:40,1');
    Route::delete('/friends/{userId}', [FriendshipController::class, 'remove']);
    Route::post('/friend-requests', [FriendshipController::class, 'send'])->middleware('throttle:30,1');
    Route::post('/friend-requests/{id}/accept', [FriendshipController::class, 'accept']);
    Route::post('/friend-requests/{id}/reject', [FriendshipController::class, 'reject']);
    Route::delete('/friend-requests/{id}', [FriendshipController::class, 'cancel']);
});

// Solicitudes para incorporar personajes a campañas (doble aprobación)
Route::get('/campaign-requests/mine', [CampaignCharacterRequestController::class, 'mine'])
    ->middleware('auth:sanctum');
Route::get('/campaigns/{campaignId}/requests', [CampaignCharacterRequestController::class, 'forCampaign'])
    ->middleware('auth:sanctum');
Route::post('/campaign-join', [CampaignCharacterRequestController::class, 'join'])
    ->middleware(['auth:sanctum', 'throttle:10,1']);
Route::post('/campaign-requests/{id}/approve', [CampaignCharacterRequestController::class, 'approve'])
    ->middleware('auth:sanctum');
Route::post('/campaign-requests/{id}/reject', [CampaignCharacterRequestController::class, 'reject'])
    ->middleware('auth:sanctum');
Route::delete('/campaign-requests/{id}', [CampaignCharacterRequestController::class, 'cancel'])
    ->middleware('auth:sanctum');
Route::post('/campaigns/{id}/invite-code', [CampaignController::class, 'regenerateInviteCode'])
    ->middleware('auth:sanctum');

Route::get('/characters', [CharacterController::class, 'myCharacters'])
    ->middleware('auth:sanctum');
Route::post(
    '/campaigns/{campaignId}/characters/{characterId}',
    [CharacterController::class, 'assignCharacter']
)->middleware('auth:sanctum');

Route::delete(
    '/campaigns/{campaignId}/characters/{characterId}',
    [CharacterController::class, 'removeCharacterFromCampaign']
)->middleware('auth:sanctum');

Route::post('/characters/{id}/image', [
    CharacterController::class,
    'uploadImage',
])
    ->middleware('auth:sanctum');


Route::post('/characters', [CharacterController::class, 'storeStandalone'])
    ->middleware('auth:sanctum');
Route::get('/characters/{id}', [CharacterController::class, 'show'])
    ->middleware('auth:sanctum');
Route::put('/characters/{id}', [CharacterController::class, 'update'])
    ->middleware('auth:sanctum');
Route::patch('/characters/{id}/details', [CharacterController::class, 'updateDetails'])
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

Route::get('/characters/{characterId}/classes/{classId}/level-up-options', [CharacterClassController::class, 'levelUpOptions'])
    ->middleware('auth:sanctum');

Route::post('/characters/{characterId}/classes/{classId}/level-up', [CharacterClassController::class, 'levelUp'])
    ->middleware('auth:sanctum');

Route::put('/characters/{characterId}/classes/{classId}', [CharacterClassController::class, 'update'])
    ->middleware('auth:sanctum');

Route::delete('/characters/{characterId}/classes/{classId}', [CharacterClassController::class, 'destroy'])
    ->middleware('auth:sanctum');

Route::get('/characters/{characterId}/classes/{classId}/subclass-options', [CharacterMagicController::class, 'subclassOptions'])->middleware('auth:sanctum');
Route::put('/characters/{characterId}/classes/{classId}/subclass', [CharacterMagicController::class, 'chooseSubclass'])->middleware('auth:sanctum');
Route::get('/characters/{characterId}/classes/{classId}/spell-options', [CharacterMagicController::class, 'spellOptions'])->middleware('auth:sanctum');
Route::put('/characters/{characterId}/classes/{classId}/spells', [CharacterMagicController::class, 'saveSpells'])->middleware('auth:sanctum');
Route::get('/characters/{characterId}/spellcasting', [CharacterMagicController::class, 'slots'])->middleware('auth:sanctum');
Route::patch('/characters/{characterId}/spell-slots/{slotId}', [CharacterMagicController::class, 'updateSlot'])->middleware('auth:sanctum');

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
Route::get('/dnd/feats/{index}', [DndClassController::class, 'feat']);
Route::get('/dnd/classes/{index}/levels/{level}', [DndClassController::class, 'level']);
Route::get('/dnd/subclasses/{index}/levels/{level}', [DndClassController::class, 'subclassLevel']);

// Rutas para la API de equipo
Route::get('/dnd/equipment', [DndEquipmentController::class, 'index']);
Route::get('/dnd/equipment/{index}', [DndEquipmentController::class, 'show']);

// Rutas para la API de razas
Route::get('/dnd/races', [DndCatalogController::class, 'races']);
Route::get('/dnd/races/{index}', [DndCatalogController::class, 'race']);

// Rutas para la API de subrazas
Route::get('/dnd/subraces', [DndCatalogController::class, 'subraces']);
Route::get('/dnd/subraces/{index}', [DndCatalogController::class, 'subrace']);

// Rutas para la API de trasfondos
Route::get('/dnd/backgrounds', [DndCatalogController::class, 'backgrounds']);
Route::get('/dnd/backgrounds/{index}', [DndCatalogController::class, 'background']);
