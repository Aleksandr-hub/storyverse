<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    /**
     * List characters with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Character::query()
            ->with(['universe:id,name', 'creator:id,username']);

        // Filter by universe
        if ($request->has('universe')) {
            $query->where('universe_id', $request->universe);
        }

        // Filter by canonical
        if ($request->has('canonical')) {
            $query->where('is_canonical', $request->boolean('canonical'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'ilike', '%'.$request->search.'%');
        }

        // Filter by creator (my characters)
        if ($request->has('creator') && $request->creator === 'me') {
            $user = $request->user();
            if ($user) {
                $query->where('creator_id', $user->id);
            }
        }

        $characters = $query->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json($characters);
    }

    /**
     * Get a single character.
     */
    public function show(Character $character): JsonResponse
    {
        $character->load(['universe:id,name', 'creator:id,username']);

        return response()->json([
            'character' => $character,
        ]);
    }

    /**
     * Create a new character.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'avatar_url' => 'nullable|url|max:500',
            'universe_id' => 'nullable|uuid|exists:universes,id',
            'traits' => 'nullable|array',
            'traits.personality' => 'nullable|string|max:255',
            'traits.skills' => 'nullable|array',
            'traits.appearance' => 'nullable|string|max:1000',
        ]);

        $character = Character::create([
            ...$validated,
            'creator_id' => $request->user()->id,
            'is_canonical' => false,
        ]);

        return response()->json([
            'message' => 'Персонажа створено',
            'character' => $character->load(['universe:id,name', 'creator:id,username']),
        ], 201);
    }

    /**
     * Update a character.
     */
    public function update(Request $request, Character $character): JsonResponse
    {
        // Check ownership
        if ($character->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не можете редагувати цього персонажа',
            ], 403);
        }

        // Can't update canonical characters
        if ($character->is_canonical) {
            return response()->json([
                'message' => 'Канонічних персонажів не можна редагувати',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'avatar_url' => 'nullable|url|max:500',
            'universe_id' => 'nullable|uuid|exists:universes,id',
            'traits' => 'nullable|array',
        ]);

        $character->update($validated);

        return response()->json([
            'message' => 'Персонажа оновлено',
            'character' => $character->fresh(['universe:id,name', 'creator:id,username']),
        ]);
    }

    /**
     * Delete a character.
     */
    public function destroy(Request $request, Character $character): JsonResponse
    {
        // Check ownership
        if ($character->creator_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не можете видалити цього персонажа',
            ], 403);
        }

        // Can't delete canonical characters
        if ($character->is_canonical) {
            return response()->json([
                'message' => 'Канонічних персонажів не можна видаляти',
            ], 403);
        }

        $character->delete();

        return response()->json([
            'message' => 'Персонажа видалено',
        ]);
    }

    /**
     * Get current user's characters.
     */
    public function myCharacters(Request $request): JsonResponse
    {
        $characters = Character::where('creator_id', $request->user()->id)
            ->with(['universe:id,name'])
            ->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json($characters);
    }
}
