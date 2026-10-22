<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DndApiService;

class DndEquipmentController extends Controller
{
    private DndApiService $dndApiService;

    public function __construct(DndApiService $dndApiService)
    {
        $this->dndApiService = $dndApiService;
    }

    public function index()
    {
        return response()->json(
            $this->dndApiService->getEquipment()
        );
    }

    public function show(string $index)
    {
        $equipment = $this->dndApiService->getEquipmentItem($index);

        if ($equipment === null) {
            return response()->json([
                'message' => 'Equipo no encontrado.',
            ], 404);
        }

        return response()->json($equipment);
    }
}