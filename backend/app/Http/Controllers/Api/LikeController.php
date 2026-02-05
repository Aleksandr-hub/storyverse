<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Like a story.
     */
    public function store(Story $story, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if already liked
        $existingLike = Like::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'message' => 'Ви вже вподобали цю історію',
                'liked' => true,
            ], 200);
        }

        // Create like
        Like::create([
            'user_id' => $user->id,
            'story_id' => $story->id,
        ]);

        // Update story like count
        $story->increment('like_count');

        return response()->json([
            'message' => 'Історію вподобано',
            'liked' => true,
            'like_count' => $story->fresh()->like_count,
        ], 201);
    }

    /**
     * Unlike a story.
     */
    public function destroy(Story $story, Request $request): JsonResponse
    {
        $user = $request->user();

        $deleted = Like::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->delete();

        if ($deleted) {
            $story->decrement('like_count');
        }

        return response()->json([
            'message' => 'Вподобання видалено',
            'liked' => false,
            'like_count' => $story->fresh()->like_count,
        ]);
    }

    /**
     * Check if user liked a story.
     */
    public function check(Story $story, Request $request): JsonResponse
    {
        $user = $request->user();

        $liked = Like::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->exists();

        return response()->json([
            'liked' => $liked,
            'like_count' => $story->like_count,
        ]);
    }
}
