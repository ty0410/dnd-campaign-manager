<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DndApiService;
use Illuminate\Http\JsonResponse;

class DndCatalogController extends Controller
{
    public function __construct(
        private DndApiService $dndApi
    ) {
    }

    public function races(): JsonResponse
    {
        return response()->json($this->dndApi->getRaces());
    }

    public function race(string $index): JsonResponse
    {
        $race = $this->dndApi->getRace($index);

        if ($race === null) {
            return response()->json([
                'message' => 'No se ha encontrado la raza solicitada.',
            ], 404);
        }

        return response()->json($race);
    }

    public function subraces(): JsonResponse
    {
        return response()->json($this->dndApi->getSubraces());
    }

    public function subrace(string $index): JsonResponse
    {
        $subrace = $this->dndApi->getSubrace($index);

        if ($subrace === null) {
            return response()->json([
                'message' => 'No se ha encontrado la subraza solicitada.',
            ], 404);
        }

        return response()->json($subrace);
    }

    public function backgrounds(): JsonResponse
    {
        return response()->json($this->dndApi->getBackgrounds());
    }

    public function background(string $index): JsonResponse
    {
        $background = $this->dndApi->getBackground($index);

        if ($background === null) {
            return response()->json([
                'message' => 'No se ha encontrado el trasfondo solicitado.',
            ], 404);
        }

        return response()->json($background);
    }
}
