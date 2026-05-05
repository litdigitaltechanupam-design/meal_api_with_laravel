<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->input('email'),
            'password' => $request->string('password')->toString(),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $tokenData = $user->issueApiToken('login');

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $tokenData['plain_text_token'],
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login')->toString();

        $user = User::query()
            ->where('phone', $login)
            ->orWhere('email', $login)
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials.',
            ], 422);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive.',
            ], 403);
        }

        $tokenData = $user->issueApiToken('login');

        return response()->json([
            'message' => 'Login successful.',
            'token' => $tokenData['plain_text_token'],
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken) {
            $request->user()
                ->apiTokens()
                ->where('token', hash('sha256', $plainTextToken))
                ->delete();
        }

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('addresses');

        return response()->json([
            'user' => $this->userPayload($user, true),
        ]);
    }

    private function userPayload(User $user, bool $withAddresses = false): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        if ($withAddresses) {
            $payload['addresses'] = $user->addresses->map(fn ($address) => [
                'id' => $address->id,
                'label' => $address->label,
                'address_line' => $address->address_line,
                'area' => $address->area,
                'city' => $address->city,
                'notes' => $address->notes,
                'is_default' => $address->is_default,
            ])->values();
        }

        return $payload;
    }
}
