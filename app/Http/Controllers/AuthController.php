<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminGate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AdminGate $gate) {}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('deleted', 0)
            ->where('suspended', 0)
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! $user->password || ! password_verify($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Invalid credentials.'],
            ]);
        }

        if (! $this->gate->isAdmin($user->id)) {
            throw ValidationException::withMessages([
                'username' => ['This account is not authorized for admin access.'],
            ]);
        }

        $token = $user->createToken(
            $request->input('device_name', 'api'),
            ['admin'],
            now()->addHours(8)
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

}
