<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DndApiService;

class DndMonsterController extends Controller
{
    private DndApiService $dndApiService;

    public function __construct(DndApiService $dndApiService)
    {
        $this->dndApiService = $dndApiService;
    }

    public function index()
    {
        return response()->json(
            $this->dndApiService->getMonsters()
        );
    }

  public function show(string $index)
{
    $monster = $this->dndApiService->getMonster($index);

    if ($monster === null) {
        return response()->json([
            'message' => 'Monstruo no encontrado.',
        ], 404);
    }

    return response()->json($monster);
}
}