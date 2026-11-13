<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Solicitudes de amistad entre usuarios.
 *
 * Los administradores pueden enviar solicitudes a administradores y a
 * usuarios normales. Los usuarios normales no pueden enviarlas a
 * administradores (tampoco los ven en el buscador).
 */
class FriendshipController extends Controller
{
    private function publicUser(?User $user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'is_admin' => $user->isAdmin(),
        ] : null;
    }

    public function index(Request $request)
    {
        $me = $request->user()->id;

        $accepted = Friendship::with(['requester', 'addressee'])
            ->where('status', Friendship::ACCEPTED)
            ->where(fn ($q) => $q->where('requester_id', $me)->orWhere('addressee_id', $me))
            ->get()
            ->map(fn ($f) => [
                'friendship_id' => $f->id,
                'user' => $this->publicUser((int) $f->requester_id === $me ? $f->addressee : $f->requester),
            ])
            ->filter(fn ($row) => $row['user'])
            ->values();

        $incoming = Friendship::with('requester')
            ->where('status', Friendship::PENDING)->where('addressee_id', $me)
            ->latest()->get()
            ->map(fn ($f) => ['id' => $f->id, 'user' => $this->publicUser($f->requester)])
            ->values();

        $outgoing = Friendship::with('addressee')
            ->where('status', Friendship::PENDING)->where('requester_id', $me)
            ->latest()->get()
            ->map(fn ($f) => ['id' => $f->id, 'user' => $this->publicUser($f->addressee)])
            ->values();

        return response()->json([
            'friends' => $accepted,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    }

    /**
     * Búsqueda por nombre de usuario (nunca por correo).
     */
    public function search(Request $request)
    {
        $viewer = $request->user();
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%' . addcslashes($term, '\\%_') . '%';

        $query = User::query()
            ->where('id', '!=', $viewer->id)
            ->whereNull('deactivated_at')
            ->whereNotNull('username')
            ->where('username', 'like', $like);

        if (! $viewer->isAdmin()) {
            $query->where('role', User::ROLE_USER);
        }

        $users = $query->orderBy('username')->limit(10)->get();

        $relations = Friendship::query()
            ->where(fn ($q) => $q->where('requester_id', $viewer->id)->whereIn('addressee_id', $users->pluck('id'))
                ->orWhere(fn ($x) => $x->where('addressee_id', $viewer->id)->whereIn('requester_id', $users->pluck('id'))))
            ->get();

        return response()->json($users->map(function ($user) use ($viewer, $relations) {
            $f = $relations->first(fn ($row) => (int) $row->requester_id === $user->id || (int) $row->addressee_id === $user->id);

            $relation = 'none';
            if ($f?->status === Friendship::ACCEPTED) {
                $relation = 'friends';
            } elseif ($f) {
                $relation = (int) $f->requester_id === (int) $viewer->id ? 'pending_sent' : 'pending_received';
            }

            return $this->publicUser($user) + ['relation' => $relation];
        })->values());
    }

    public function send(Request $request)
    {
        $validated = $request->validate(['user_id' => ['required', 'integer']]);

        $viewer = $request->user();
        $target = User::whereNull('deactivated_at')->find($validated['user_id']);

        if (! $target || $target->id === $viewer->id) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if (! $viewer->isAdmin() && $target->isAdmin()) {
            return response()->json([
                'message' => 'No puedes enviar solicitudes de amistad a administradores.',
            ], 403);
        }

        $existing = Friendship::between($viewer->id, $target->id)->first();

        if ($existing) {
            $message = $existing->status === Friendship::ACCEPTED
                ? 'Ya sois amigos.'
                : ((int) $existing->requester_id === (int) $viewer->id
                    ? 'Ya has enviado una solicitud a este usuario.'
                    : 'Este usuario ya te ha enviado una solicitud: acéptala desde Amigos.');

            return response()->json(['message' => $message], 422);
        }

        Friendship::create([
            'requester_id' => $viewer->id,
            'addressee_id' => $target->id,
            'status' => Friendship::PENDING,
        ]);

        return response()->json(['message' => "Solicitud enviada a @{$target->username}."], 201);
    }

    public function accept(Request $request, $id)
    {
        $friendship = Friendship::where('status', Friendship::PENDING)
            ->where('addressee_id', $request->user()->id)->find($id);

        if (! $friendship) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $friendship->update(['status' => Friendship::ACCEPTED]);

        return response()->json(['message' => 'Solicitud aceptada.']);
    }

    public function reject(Request $request, $id)
    {
        $friendship = Friendship::where('status', Friendship::PENDING)
            ->where('addressee_id', $request->user()->id)->find($id);

        if (! $friendship) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Solicitud rechazada.']);
    }

    /** El remitente retira una solicitud pendiente. */
    public function cancel(Request $request, $id)
    {
        $friendship = Friendship::where('status', Friendship::PENDING)
            ->where('requester_id', $request->user()->id)->find($id);

        if (! $friendship) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Solicitud cancelada.']);
    }

    /** Elimina una amistad existente (cualquiera de las dos partes). */
    public function remove(Request $request, $userId)
    {
        $deleted = Friendship::between($request->user()->id, (int) $userId)
            ->where('status', Friendship::ACCEPTED)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Amistad no encontrada.'], 404);
        }

        return response()->json(['message' => 'Amistad eliminada.']);
    }
}
