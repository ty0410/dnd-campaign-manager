<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DndApiService;

class DndClassController extends Controller
{
    private DndApiService $dndApiService;

    public function __construct(DndApiService $dndApiService)
    {
        $this->dndApiService = $dndApiService;
    }

    public function index()
    {
        return response()->json(
            $this->dndApiService->getClasses()
        );
    }

    public function show(string $index)
    {
        $class = $this->dndApiService->getClass($index);

        if ($class === null) {
            return response()->json([
                'message' => 'Clase no encontrada.',
            ], 404);
        }

        return response()->json($class);
    }
}