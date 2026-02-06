<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update authenticated user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'min:3', 'max:50', 'unique:users,username,'.$user->id, 'regex:/^[a-zA-Z0-9_-]+$/'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
        ], [
            'username.regex' => 'Username може містити лише літери, цифри, дефіс та підкреслення.',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Профіль оновлено.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ];

        // Require current password if user has one
        if ($user->hasPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Пароль оновлено.',
        ]);
    }
}
