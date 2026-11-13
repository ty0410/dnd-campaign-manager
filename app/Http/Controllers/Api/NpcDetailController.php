<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Npc;
use App\Models\NpcAttribute;
use App\Models\NpcNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Ficha completa de un PNJ: foto, características personalizadas
 * (visibles u ocultas para los jugadores) y apuntes de cada jugador.
 */
class NpcDetailController extends Controller
{
    /** PNJ al que el usuario tiene acceso por ser miembro de su campaña. */
    private function accessibleNpc(Request $request, $id): ?Npc
    {
        $npc = Npc::with('campaign')->find($id);

        if (! $npc || ! $request->user()->campaigns()->where('campaigns.id', $npc->campaign_id)->exists()) {
            return null;
        }

        return $npc;
    }

    private function isMaster(Request $request, Npc $npc): bool
    {
        return (int) $npc->campaign->master_id === (int) $request->user()->id;
    }

    public function show(Request $request, $id)
    {
        $npc = $this->accessibleNpc($request, $id);

        if (! $npc) {
            return response()->json(['message' => 'NPC no encontrado.'], 404);
        }

        $isMaster = $this->isMaster($request, $npc);

        $attributes = $npc->customAttributes()
            ->when(! $isMaster, fn ($q) => $q->where('is_visible', true))
            ->get();

        // El Dungeon Master ve los apuntes de todos; cada jugador, solo los suyos.
        $notes = $npc->notes()
            ->with('user:id,username,avatar_path')
            ->when(! $isMaster, fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($note) => [
                'id' => $note->id,
                'body' => $note->body,
                'updated_at' => $note->updated_at,
                'mine' => (int) $note->user_id === (int) $request->user()->id,
                'user' => [
                    'id' => $note->user?->id,
                    'username' => $note->user?->username,
                    'avatar_url' => $note->user?->avatar_url,
                ],
            ]);

        return response()->json([
            'npc' => $npc->makeHidden('campaign'),
            'campaign' => ['id' => $npc->campaign->id, 'name' => $npc->campaign->name],
            'is_master' => $isMaster,
            'custom_attributes' => $attributes,
            'notes' => $notes,
        ]);
    }

    // ---- Foto -----------------------------------------------------------

    public function uploadImage(Request $request, $id)
    {
        $npc = $this->accessibleNpc($request, $id);

        if (! $npc) {
            return response()->json(['message' => 'NPC no encontrado.'], 404);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($npc->image_path) {
            Storage::disk('public')->delete($npc->image_path);
        }

        $npc->image_path = $request->file('image')->store('npcs', 'public');
        $npc->save();

        return response()->json(['message' => 'Foto actualizada.', 'npc' => $npc->makeHidden('campaign')]);
    }

    public function deleteImage(Request $request, $id)
    {
        $npc = $this->accessibleNpc($request, $id);

        if (! $npc) {
            return response()->json(['message' => 'NPC no encontrado.'], 404);
        }

        if ($npc->image_path) {
            Storage::disk('public')->delete($npc->image_path);
            $npc->image_path = null;
            $npc->save();
        }

        return response()->json(['message' => 'Foto eliminada.', 'npc' => $npc->makeHidden('campaign')]);
    }

    // ---- Apuntes del jugador ---------------------------------------------

    public function saveNote(Request $request, $id)
    {
        $npc = $this->accessibleNpc($request, $id);

        if (! $npc) {
            return response()->json(['message' => 'NPC no encontrado.'], 404);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:3000'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $existing = NpcNote::where('npc_id', $npc->id)->where('user_id', $request->user()->id)->first();

        if ($body === '') {
            $existing?->delete();

            return response()->json(['message' => 'Apunte eliminado.']);
        }

        NpcNote::updateOrCreate(
            ['npc_id' => $npc->id, 'user_id' => $request->user()->id],
            ['body' => $body]
        );

        return response()->json(['message' => 'Apunte guardado.']);
    }

    // ---- Características personalizadas (solo Dungeon Master) -------------

    private function masterNpc(Request $request, $id): ?Npc
    {
        $npc = $this->accessibleNpc($request, $id);

        return $npc && $this->isMaster($request, $npc) ? $npc : null;
    }

    private function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:1000'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    public function storeAttribute(Request $request, $id)
    {
        $npc = $this->masterNpc($request, $id);

        if (! $npc) {
            return response()->json(['message' => 'Solo el Dungeon Master puede añadir características.'], 403);
        }

        $validated = $request->validate($this->rules());

        $attribute = $npc->customAttributes()->create([
            'label' => $validated['label'],
            'value' => $validated['value'],
            'is_visible' => $validated['is_visible'] ?? true,
            'sort_order' => ((int) $npc->customAttributes()->max('sort_order')) + 1,
        ]);

        return response()->json(['message' => 'Característica añadida.', 'attribute' => $attribute], 201);
    }

    public function updateAttribute(Request $request, $id)
    {
        $attribute = NpcAttribute::find($id);
        $npc = $attribute ? $this->masterNpc($request, $attribute->npc_id) : null;

        if (! $attribute || ! $npc) {
            return response()->json(['message' => 'Característica no encontrada.'], 404);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'value' => ['sometimes', 'required', 'string', 'max:1000'],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $attribute->update($validated);

        return response()->json(['message' => 'Característica actualizada.', 'attribute' => $attribute]);
    }

    public function destroyAttribute(Request $request, $id)
    {
        $attribute = NpcAttribute::find($id);
        $npc = $attribute ? $this->masterNpc($request, $attribute->npc_id) : null;

        if (! $attribute || ! $npc) {
            return response()->json(['message' => 'Característica no encontrada.'], 404);
        }

        $attribute->delete();

        return response()->json(['message' => 'Característica eliminada.']);
    }
}
