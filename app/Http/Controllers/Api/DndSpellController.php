<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DndApiService;

class DndSpellController extends Controller
{
    private DndApiService $dndApiService;

    public function __construct(DndApiService $dndApiService)
    {
        $this->dndApiService = $dndApiService;
    }

    public function index()
    {
        return response()->json(
            $this->dndApiService->getSpells()
        );
    }

    public function show(string $index)
    {
        $spell = $this->dndApiService->getSpell($index);

        if ($spell === null) {
            return response()->json([
                'message' => 'Hechizo no encontrado.',
            ], 404);
        }

        return response()->json($spell);
    }
}