<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'nama_perangkat' => ['required', 'string', 'max:50'],
            'mode' => ['required', 'in:baca,kelola'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau kata sandi tidak sesuai.',
            ], 401);
        }

        if ($data['mode'] === 'kelola' && ! $user->is_admin) {
            return response()->json([
                'message' => 'Hanya pengelola yang dapat menggunakan mode kelola.',
            ], 403);
        }

        $abilities = $data['mode'] === 'kelola'
            ? ['book:read', 'book:write']
            : ['book:read'];

        $token = $user->createToken(
            $data['nama_perangkat'],
            $abilities,
            now()->addHours(2)
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_in' => 7200,
            'abilities' => $abilities,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => (int) $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token saat ini sudah dicabut.',
        ]);
    }
}
