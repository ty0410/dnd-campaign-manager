<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Perfil propio y perfil público (nombre de usuario y foto).
 * El correo y el nombre real no se muestran a otros usuarios.
 */
class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes', 'required', 'string', 'min:3', 'max:30',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado.',
            'user' => $user->fresh(),
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        $user->save();

        return response()->json([
            'message' => 'Foto de perfil actualizada.',
            'user' => $user->fresh(),
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Foto de perfil eliminada.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Perfil público de un usuario, con la relación que tiene con quien mira.
     */
    public function show(Request $request, string $username)
    {
        $viewer = $request->user();

        $user = User::where('username', $username)->whereNull('deactivated_at')->first();

        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $friendship = $viewer->id === $user->id
            ? null
            : Friendship::between($viewer->id, $user->id)->first();

        $relation = 'none';
        if ($viewer->id === $user->id) {
            $relation = 'self';
        } elseif ($friendship?->status === Friendship::ACCEPTED) {
            $relation = 'friends';
        } elseif ($friendship) {
            $relation = (int) $friendship->requester_id === (int) $viewer->id ? 'pending_sent' : 'pending_received';
        }

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'is_admin' => $user->isAdmin(),
            'member_since' => $user->created_at,
            'relation' => $relation,
            'friendship_id' => $friendship?->id,
            // Un usuario normal no puede enviar solicitudes a un administrador.
            'can_request' => $relation === 'none' && ($viewer->isAdmin() || ! $user->isAdmin()),
        ]);
    }
}
