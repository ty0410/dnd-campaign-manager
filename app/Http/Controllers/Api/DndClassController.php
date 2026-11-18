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

    /** Lista de conjuros de una clase lanzadora (para añadir conjuros desde la ficha). */
    public function spells(string $index)
    {
        try {
            return response()->json($this->dndApiService->getClassSpells($index));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo consultar el catálogo.'], 502);
        }
    }

    /** Conjuros de una clase con su nivel y escuela, para mostrarlos agrupados. */
    public function spellList(string $index)
    {
        try {
            $list = \Illuminate\Support\Facades\Cache::remember('dnd_class_spell_list_' . $index, now()->addDay(), function () use ($index) {
                $results = collect($this->dndApiService->getClassSpells($index)['results'] ?? []);
                $details = collect($this->dndApiService->getSpellDetailsForIndexes($results->pluck('index')->all()))->keyBy('index');

                return $results->map(function ($spell) use ($details) {
                    $detail = $details->get($spell['index']);

                    return $detail ? [
                        'index' => $detail['index'],
                        'name' => $detail['name'],
                        'level' => (int) ($detail['level'] ?? 0),
                        'school' => data_get($detail, 'school.name'),
                    ] : null;
                })->filter()->sortBy([['level', 'asc'], ['name', 'asc']])->values()->all();
            });

            return response()->json($list);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'No se pudo consultar el catálogo.'], 502);
        }
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