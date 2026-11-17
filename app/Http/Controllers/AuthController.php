<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'nama_perangkat' => 'required|string|max:50',
            'mode' => 'required|in:baca,kelola',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau kata sandi tidak sesuai'
            ], 401);
        }

        $abilities = $data['mode'] === 'baca' ? ['book:read'] : ['book:read', 'book:write'];

        $token = $user->createToken($data['nama_perangkat'], $abilities, now()->addHours(2));

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_in' => 7200,
            'abilities' => $abilities,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json(['data' => [
            'id' => $user->id,
            'nama' => $user->name,
            'email' => $user->email,
            'is_admin' =>  $user->is_admin,
        ]]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Token saat ini sudah dicabut.',
        ]);
    }
}
