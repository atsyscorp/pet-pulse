<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OwnerRegisterRequest;
use App\Http\Resources\Owner\OwnerResource;
use App\Models\Owner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(OwnerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $owner = Owner::query()->create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'terms_accepted_at' => now(),
        ]);

        return $this->tokenResponse($owner, $data['device_name'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ], [], ['email' => 'correo electrónico', 'password' => 'contraseña', 'device_name' => 'nombre del dispositivo']);

        $owner = Owner::query()->where('email', mb_strtolower(trim($data['email'])))->first();

        if ($owner === null || ! Hash::check($data['password'], $owner->password)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        return $this->tokenResponse($owner, $data['device_name']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): OwnerResource
    {
        return new OwnerResource($request->user());
    }

    private function tokenResponse(Owner $owner, string $deviceName, int $status = 200): JsonResponse
    {
        // Scoped ability: an owner token can never reach staff features.
        $token = $owner->createToken($deviceName, ['owner']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'owner' => new OwnerResource($owner),
        ], $status);
    }
}
