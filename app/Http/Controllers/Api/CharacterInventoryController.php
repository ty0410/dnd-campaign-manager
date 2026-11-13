<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\CharacterInventory;
use App\Services\DndApiService;
use Illuminate\Http\Request;

/**
 * Objetos del personaje: del catálogo de la API (equipment_index) o personalizados.
 * Solo el propietario del personaje puede gestionarlos.
 */
class CharacterInventoryController extends Controller
{
    private const BODY_ARMOR = ['Light', 'Medium', 'Heavy'];

    private function ownCharacter(Request $request, $characterId): ?Character
    {
        return Character::where('user_id', $request->user()->id)->find($characterId);
    }

    public function store(Request $request, $characterId, DndApiService $dnd)
    {
        $character = $this->ownCharacter($request, $characterId);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate([
            'equipment_index' => ['nullable', 'string', 'max:150'],
            'item_name' => ['nullable', 'string', 'max:255', 'required_without:equipment_index'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Solo objetos personalizados:
            'custom_kind' => ['nullable', 'in:item,light,medium,heavy,shield'],
            'armor_base' => ['nullable', 'integer', 'min:0', 'max:30'],
            'stackable' => ['nullable', 'boolean'],
        ]);

        $quantity = $validated['quantity'] ?? 1;
        $name = $validated['item_name'] ?? null;
        $weight = $validated['weight'] ?? null;
        $data = null;

        if (! empty($validated['equipment_index'])) {
            try {
                $item = $dnd->getEquipmentItem($validated['equipment_index']);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'No se pudo consultar el catálogo. Inténtalo de nuevo.'], 502);
            }

            if (! $item) {
                return response()->json(['message' => 'Objeto no encontrado en el catálogo.'], 404);
            }

            $name = $item['name'];
            // El catálogo da el peso en libras.
            $weight = isset($item['weight']) ? round($item['weight'] * 0.4536, 2) : null;
            $data = CharacterInventory::dataFromCatalog($item);

            $existing = CharacterInventory::where('character_id', $character->id)
                ->where('equipment_index', $validated['equipment_index'])
                ->first();

            if ($existing) {
                // Solo se acumulan los objetos que suelen tenerse en cantidad.
                if (! ($data['stackable'] ?? false)) {
                    return response()->json(['message' => "Ya tienes «{$name}»."], 422);
                }

                $existing->increment('quantity', $quantity);

                return response()->json(['message' => "Se han añadido {$quantity} × {$name}.", 'item' => $existing->fresh()]);
            }

            if (! ($data['stackable'] ?? false)) {
                $quantity = 1;
            }
        } else {
            $data = $this->customData($validated);
        }

        $entry = CharacterInventory::create([
            'character_id' => $character->id,
            'equipment_index' => $validated['equipment_index'] ?? null,
            'equipment_data' => $data,
            'item_name' => $name,
            'quantity' => $quantity,
            'weight' => $weight,
            'is_equipped' => false,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => "«{$name}» añadido al inventario.", 'item' => $entry], 201);
    }

    /** Datos de un objeto personalizado (armadura o escudo con su CA base). */
    private function customData(array $validated): array
    {
        $kind = $validated['custom_kind'] ?? 'item';
        $base = (int) ($validated['armor_base'] ?? 0);

        $armor = match ($kind) {
            'light' => ['Light', ['base' => $base, 'dex_bonus' => true, 'max_bonus' => null]],
            'medium' => ['Medium', ['base' => $base, 'dex_bonus' => true, 'max_bonus' => 2]],
            'heavy' => ['Heavy', ['base' => $base, 'dex_bonus' => false, 'max_bonus' => null]],
            'shield' => ['Shield', ['base' => $base ?: 2, 'dex_bonus' => false, 'max_bonus' => null]],
            default => [null, null],
        };

        return [
            'category' => $armor[0] ? 'Armor' : null,
            'armor_category' => $armor[0],
            'armor_class' => $armor[1],
            'stackable' => (bool) ($validated['stackable'] ?? false),
        ];
    }

    public function update(Request $request, $characterId, $itemId, DndApiService $dnd)
    {
        $character = $this->ownCharacter($request, $characterId);
        $item = $character ? CharacterInventory::where('character_id', $character->id)->find($itemId) : null;

        if (! $item) {
            return response()->json(['message' => 'Objeto no encontrado.'], 404);
        }

        $validated = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'is_equipped' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        // Objetos añadidos antes de guardar los datos de armadura.
        if ($item->equipment_data === null && $item->equipment_index) {
            try {
                if ($detail = $dnd->getEquipmentItem($item->equipment_index)) {
                    $item->equipment_data = CharacterInventory::dataFromCatalog($detail);
                    $item->save();
                }
            } catch (\Throwable $e) {
                // Se sigue sin datos de armadura.
            }
        }

        // Solo una armadura y un escudo equipados a la vez.
        if (! empty($validated['is_equipped'])) {
            $category = $item->equipment_data['armor_category'] ?? null;
            $group = in_array($category, self::BODY_ARMOR, true) ? self::BODY_ARMOR : ($category === 'Shield' ? ['Shield'] : null);

            if ($group) {
                CharacterInventory::where('character_id', $character->id)
                    ->where('id', '!=', $item->id)
                    ->where('is_equipped', true)
                    ->get()
                    ->filter(fn ($other) => in_array($other->equipment_data['armor_category'] ?? null, $group, true))
                    ->each(fn ($other) => $other->update(['is_equipped' => false]));
            }
        }

        $item->update($validated);

        return response()->json(['message' => 'Objeto actualizado.', 'item' => $item->fresh()]);
    }

    public function destroy(Request $request, $characterId, $itemId)
    {
        $character = $this->ownCharacter($request, $characterId);
        $item = $character ? CharacterInventory::where('character_id', $character->id)->find($itemId) : null;

        if (! $item) {
            return response()->json(['message' => 'Objeto no encontrado.'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Objeto eliminado.']);
    }
}
