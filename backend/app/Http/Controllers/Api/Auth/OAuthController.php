<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class OAuthController extends Controller
{
    /**
     * Supported OAuth providers.
     */
    protected array $providers = ['google', 'facebook', 'github'];

    /**
     * Redirect to OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (! in_array($provider, $this->providers)) {
            return response()->json([
                'message' => 'Непідтримуваний провайдер: '.$provider,
            ], 400);
        }

        return Socialite::driver($provider)
            ->stateless()
            ->redirect();
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        if (! in_array($provider, $this->providers)) {
            return response()->json([
                'message' => 'Непідтримуваний провайдер: '.$provider,
            ], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->user();
        } catch (InvalidStateException $e) {
            return response()->json([
                'message' => 'Помилка авторизації. Спробуйте ще раз.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Помилка отримання даних від провайдера.',
            ], 500);
        }

        // Find existing user by OAuth ID or email
        $user = User::where('oauth_provider', $provider)
            ->where('oauth_id', $socialUser->getId())
            ->first();

        if (! $user) {
            // Check if user with this email exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link OAuth to existing account
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_id' => $socialUser->getId(),
                    'avatar_url' => $user->avatar_url ?? $socialUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'email' => $socialUser->getEmail(),
                    'username' => $this->generateUsername($socialUser),
                    'oauth_provider' => $provider,
                    'oauth_id' => $socialUser->getId(),
                    'avatar_url' => $socialUser->getAvatar(),
                    'email_verified_at' => now(), // OAuth emails are verified
                ]);
            }
        } else {
            // Update avatar if changed
            if ($socialUser->getAvatar() && ! $user->avatar_url) {
                $user->update(['avatar_url' => $socialUser->getAvatar()]);
            }
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Ваш акаунт деактивовано.',
            ], 403);
        }

        $token = $user->createToken('oauth-'.$provider)->plainTextToken;

        return response()->json([
            'message' => 'Авторизація успішна.',
            'user' => $user,
            'token' => $token,
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }

    /**
     * Generate unique username from OAuth data.
     */
    protected function generateUsername($socialUser): string
    {
        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0];
        $baseUsername = Str::slug($name, '_');
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.'_'.$counter;
            $counter++;
        }

        return Str::limit($username, 50, '');
    }
}
