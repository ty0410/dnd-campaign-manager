<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $user = \App\Models\User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => $validated['password'],
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Usuario registrado correctamente.',
        'user' => $user,
        'token' => $token,
    ], 201);
}
public function login(Request $request)
{
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (!\Illuminate\Support\Facades\Auth::attempt([
        'email' => $validated['email'],
        'password' => $validated['password'],
    ])) {
        return response()->json([
            'message' => 'Las credenciales no son correctas.',
        ], 401);
    }

    $user = \App\Models\User::where('email', $validated['email'])->first();

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Inicio de sesión correcto.',
        'user' => $user,
        'token' => $token,
    ]);
}
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Sesión cerrada correctamente.',
    ]);
}
}
