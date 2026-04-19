<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $colors = ['#7c3aed', '#ec4899', '#10b981', '#f59e0b', '#3b82f6', '#ef4444'];
        $data['avatar_color'] = $colors[array_rand($colors)];
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('app')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user->tokens()->delete();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('app')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateFcmToken(Request $request)
    {
        $data = $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $data['fcm_token']]);
        return response()->json(['message' => 'Token atualizado.']);
    }
}
