<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get user profile by ID.
     */
    public function show(User $user): JsonResponse
    {
        $storiesCount = $user->stories()->where('status', 'published')->count();
        $totalViews = $user->stories()->where('status', 'published')->sum('view_count');
        $totalLikes = $user->stories()->where('status', 'published')->sum('like_count');

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'is_premium' => $user->is_premium,
            'created_at' => $user->created_at,
            'stats' => [
                'stories_count' => $storiesCount,
                'total_views' => $totalViews,
                'total_likes' => $totalLikes,
            ],
        ]);
    }

    /**
     * Get user's published stories.
     */
    public function stories(User $user, Request $request): JsonResponse
    {
        $stories = $user->stories()
            ->where('status', 'published')
            ->where('is_public', true)
            ->with(['author:id,username,avatar_url', 'universe:id,name', 'tags:id,name'])
            ->withCount('chapters')
            ->orderByDesc('published_at')
            ->paginate($request->input('per_page', 12));

        return response()->json([
            'data' => $stories->items(),
            'meta' => [
                'current_page' => $stories->currentPage(),
                'last_page' => $stories->lastPage(),
                'per_page' => $stories->perPage(),
                'total' => $stories->total(),
            ],
        ]);
    }
}
