<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/social/{provider}
     *
     * Accepts an OAuth token from the frontend, verifies it with the provider,
     * finds or creates a user, and returns a Sanctum token.
     */
    public function handleProvider(Request $request, string $provider): JsonResponse
    {
        // Validate provider
        if (!in_array($provider, ['google', 'facebook', 'apple'])) {
            return $this->error('Unsupported social provider.', 422);
        }

        // Check if provider is enabled in settings
        if (!Setting::get("social.{$provider}_enabled", false)) {
            return $this->error('This social login provider is not enabled.', 403);
        }

        $request->validate([
            'token' => 'required|string',
            'name' => 'nullable|string|max:255', // For Apple first-time login
        ]);

        try {
            // Use token-based stateless validation
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($request->input('token'));
        } catch (\Throwable $e) {
            return $this->error('Invalid social login token: ' . $e->getMessage(), 401);
        }

        if (!$socialUser) {
            return $this->error('Could not retrieve user from social provider.', 401);
        }

        $email = $socialUser->getEmail();
        $socialId = $socialUser->getId();
        $name = $socialUser->getName() ?? $request->input('name') ?? 'User';
        $avatar = $socialUser->getAvatar();

        if (!$email) {
            return $this->error('Email not provided by social provider. Please use another login method.', 422);
        }

        // Find existing user by social provider+id OR email
        $user = User::withoutGlobalScopes()
            ->where(function ($q) use ($provider, $socialId, $email) {
                $q->where(fn($q2) => $q2->where('social_provider', $provider)->where('social_id', $socialId))
                  ->orWhere('email', strtolower($email));
            })
            ->first();

        if ($user) {
            // Link social if not already linked
            if (!$user->social_provider) {
                $user->update([
                    'social_provider' => $provider,
                    'social_id' => $socialId,
                ]);
            }

            // Update avatar if not set
            if (!$user->avatar && $avatar) {
                $user->update(['avatar' => $avatar]);
            }

            // Check banned
            if ($user->is_banned) {
                return $this->error('Your account has been suspended.', 403);
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $name,
                'email' => strtolower($email),
                'password' => Hash::make(Str::random(32)),
                'is_active' => true,
                'social_provider' => $provider,
                'social_id' => $socialId,
                'avatar' => $avatar,
                'email_verified_at' => now(),
            ]);
        }

        // Create Sanctum token
        $token = $user->createToken('social-auth')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in successfully.');
    }
}
