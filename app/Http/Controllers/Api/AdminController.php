<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Gestión exclusiva de administradores.
 *
 * Un administrador puede buscar, filtrar, modificar y eliminar los
 * personajes y campañas de los usuarios normales, y crear nuevas cuentas
 * de administrador. La ruta está protegida por el middleware «admin».
 */
class AdminController extends Controller
{
    private const PER_PAGE = 15;

    // ------------------------------------------------------------------
    // Resumen y usuarios
    // ------------------------------------------------------------------

    public function stats()
    {
        $normal = User::where('role', User::ROLE_USER);

        return response()->json([
            'users_active' => (clone $normal)->whereNull('deactivated_at')->count(),
            'users_deactivated' => (clone $normal)->whereNotNull('deactivated_at')->count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'characters' => Character::whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER))->count(),
            'campaigns' => Campaign::whereHas('master', fn ($q) => $q->where('role', User::ROLE_USER))->count(),
            'recent_characters' => Character::with('user:id,name,username')
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER))
                ->latest('id')->limit(5)->get(['id', 'name', 'race', 'level', 'user_id', 'image_path']),
            'recent_campaigns' => Campaign::with('master:id,name,username')
                ->whereHas('master', fn ($q) => $q->where('role', User::ROLE_USER))
                ->latest('id')->limit(5)->get(['id', 'name', 'master_id']),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query()
            ->where('role', User::ROLE_USER)
            ->withCount(['characters', 'masteredCampaigns']);

        if ($search = $this->term($request->query('search'))) {
            $like = $this->like($search);

            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        match ($request->query('status')) {
            'active' => $query->whereNull('deactivated_at'),
            'deactivated' => $query->whereNotNull('deactivated_at'),
            default => null,
        };

        match ($request->query('sort')) {
            'name' => $query->orderBy('name'),
            'characters' => $query->orderByDesc('characters_count')->orderBy('name'),
            default => $query->latest('id'),
        };

        return response()->json($query->paginate(self::PER_PAGE)->withQueryString());
    }

    /** Da de baja a un usuario normal: conserva su contenido y le impide entrar. */
    public function deactivateUser($id)
    {
        $user = User::where('role', User::ROLE_USER)->find($id);

        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $user->deactivated_at = now();
        $user->save();

        // Cierra todas sus sesiones abiertas.
        $user->tokens()->delete();

        return response()->json(['message' => "«{$user->name}» ha sido dado de baja.", 'user' => $user->fresh()]);
    }

    public function reactivateUser($id)
    {
        $user = User::where('role', User::ROLE_USER)->find($id);

        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $user->deactivated_at = null;
        $user->save();

        return response()->json(['message' => "«{$user->name}» ha sido reactivado.", 'user' => $user->fresh()]);
    }

    // ------------------------------------------------------------------
    // Personajes
    // ------------------------------------------------------------------

    public function characters(Request $request)
    {
        $query = Character::query()
            ->with(['user:id,name,email', 'campaigns:id,name'])
            ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER));

        if ($search = $this->term($request->query('search'))) {
            $like = $this->like($search);

            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('race', 'like', $like)
                    ->orWhereHas('user', function ($u) use ($like) {
                        $u->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        }

        if ($campaign = $this->term($request->query('campaign'))) {
            $like = $this->like($campaign);
            $query->whereHas('campaigns', fn ($q) => $q->where('campaigns.name', 'like', $like));
        }

        match ($request->query('membership')) {
            'with' => $query->has('campaigns'),
            'without' => $query->doesntHave('campaigns'),
            default => null,
        };

        match ($request->query('sort')) {
            'name' => $query->orderBy('name'),
            'level' => $query->orderByDesc('level')->orderBy('name'),
            default => $query->latest('id'),
        };

        return response()->json($query->paginate(self::PER_PAGE)->withQueryString());
    }

    public function updateCharacter(Request $request, $id)
    {
        $character = $this->managedCharacter($id);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'personality_trait' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'ideal' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'bond' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'flaw' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'current_hit_points' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'maximum_hit_points' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'temporary_hit_points' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $character->update($validated);

        return response()->json([
            'message' => 'Personaje actualizado.',
            'character' => $character->fresh()->load(['user:id,name,email', 'campaigns:id,name']),
        ]);
    }

    public function destroyCharacter($id)
    {
        $character = $this->managedCharacter($id);

        if (! $character) {
            return response()->json(['message' => 'Personaje no encontrado.'], 404);
        }

        if ($character->image_path) {
            Storage::disk('public')->delete($character->image_path);
        }

        $character->delete();

        return response()->json(['message' => 'Personaje eliminado.']);
    }

    // ------------------------------------------------------------------
    // Campañas
    // ------------------------------------------------------------------

    public function campaigns(Request $request)
    {
        $query = Campaign::query()
            ->with('master:id,name,email')
            ->withCount(['characters', 'users'])
            ->whereHas('master', fn ($q) => $q->where('role', User::ROLE_USER));

        if ($search = $this->term($request->query('search'))) {
            $like = $this->like($search);

            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('master', function ($m) use ($like) {
                        $m->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        }

        match ($request->query('sort')) {
            'name' => $query->orderBy('name'),
            'characters' => $query->orderByDesc('characters_count')->orderBy('name'),
            default => $query->latest('id'),
        };

        return response()->json($query->paginate(self::PER_PAGE)->withQueryString());
    }

    public function showCampaign($id)
    {
        $campaign = $this->managedCampaign($id);

        if (! $campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        $campaign->load([
            'master:id,name,email',
            'characters:id,name,race,level,user_id',
            'characters.user:id,name',
            'gameSessions:id,campaign_id,title,session_date',
            'locations:id,campaign_id,name,type',
            'npcs:id,campaign_id,name,role',
            'encounters:id,campaign_id,name,status',
        ]);

        return response()->json($campaign);
    }

    public function updateCampaign(Request $request, $id)
    {
        $campaign = $this->managedCampaign($id);

        if (! $campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $campaign->update($validated);

        return response()->json([
            'message' => 'Campaña actualizada.',
            'campaign' => $campaign->fresh()->load('master:id,name,email'),
        ]);
    }

    public function destroyCampaign($id)
    {
        $campaign = $this->managedCampaign($id);

        if (! $campaign) {
            return response()->json(['message' => 'Campaña no encontrada.'], 404);
        }

        if ($campaign->image_path) {
            Storage::disk('public')->delete($campaign->image_path);
        }

        $campaign->delete();

        return response()->json(['message' => 'Campaña eliminada.']);
    }

    // ------------------------------------------------------------------
    // Cuentas de administrador
    // ------------------------------------------------------------------

    public function admins()
    {
        return response()->json(
            User::where('role', User::ROLE_ADMIN)
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'email', 'created_at'])
        );
    }

    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
        ]);
        // El rol nunca se asigna en masa: solo aquí, de forma explícita.
        $user->role = User::ROLE_ADMIN;
        $user->save();

        return response()->json([
            'message' => 'Cuenta de administrador creada.',
            'user' => $user->only(['id', 'name', 'username', 'email', 'created_at']),
        ], 201);
    }

    // ------------------------------------------------------------------

    /** Solo se gestionan elementos de usuarios normales. */
    private function managedCharacter($id): ?Character
    {
        return Character::whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER))->find($id);
    }

    private function managedCampaign($id): ?Campaign
    {
        return Campaign::whereHas('master', fn ($q) => $q->where('role', User::ROLE_USER))->find($id);
    }

    private function term($value): string
    {
        return trim((string) $value);
    }

    private function like(string $term): string
    {
        return '%' . addcslashes($term, '\\%_') . '%';
    }
}
