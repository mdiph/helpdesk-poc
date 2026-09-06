<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login - exchange credentials for a bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'api-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been disabled.',
            ]);
        }

        RateLimiter::clear($key);

        $token = $user->createToken(
            $request->input('device_name', 'api-token'),
            ['*'],
            $expiresAt = config('sanctum.expiration')
                ? now()->addMinutes((int) config('sanctum.expiration'))
                : null,
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt,
            'user' => new UserResource($user->loadMissing('role')),
        ]);
    }

    /** GET /api/auth/me */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->loadMissing('role'));
    }

    /** POST /api/auth/logout - revoke the token used for this request. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
