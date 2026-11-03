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

    public function level(string $index, int $level)
    {
        if ($level < 1 || $level > 20) {
            return response()->json(['message' => 'El nivel debe estar entre 1 y 20.'], 422);
        }

        $details = $this->dndApiService->getClassLevel($index, $level);
        if ($details === null) {
            return response()->json(['message' => 'No se encontró la progresión de esa clase para ese nivel.'], 404);
        }

        return response()->json($details);
    }

    public function subclassLevel(string $index, int $level)
    {
        if ($level < 1 || $level > 20) return response()->json(['message' => 'El nivel debe estar entre 1 y 20.'], 422);
        $details = $this->dndApiService->getSubclassLevel($index, $level);
        if ($details === null) return response()->json(['message' => 'No se encontró la progresión de esa subclase.'], 404);
        return response()->json($details);
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

    public function feat(string $index)
    {
        $feat = $this->dndApiService->getFeat($index);
        if ($feat === null) return response()->json(['message' => 'Dote no encontrada.'], 404);
        return response()->json($feat);
    }

}