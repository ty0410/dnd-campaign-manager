<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CharacterController;

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

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

    