<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Get comments for a story.
     */
    public function index(Story $story, Request $request): JsonResponse
    {
        $comments = Comment::where('story_id', $story->id)
            ->whereNull('parent_id') // Only top-level comments
            ->with([
                'user:id,username,avatar_url',
                'replies' => function ($query) {
                    $query->with('user:id,username,avatar_url')
                        ->orderBy('created_at', 'asc');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $comments->items(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Create a new comment.
     */
    public function store(Story $story, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
            'chapter_id' => ['nullable', 'uuid', 'exists:chapters,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:comments,id'],
        ]);

        $comment = Comment::create([
            'story_id' => $story->id,
            'user_id' => $request->user()->id,
            'chapter_id' => $validated['chapter_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        $comment->load('user:id,username,avatar_url');

        return response()->json([
            'message' => 'Коментар додано',
            'comment' => $comment,
        ], 201);
    }

    /**
     * Update a comment.
     */
    public function update(Comment $comment, Request $request): JsonResponse
    {
        // Check ownership
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не можете редагувати цей коментар',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $comment->update($validated);
        $comment->load('user:id,username,avatar_url');

        return response()->json([
            'message' => 'Коментар оновлено',
            'comment' => $comment,
        ]);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment, Request $request): JsonResponse
    {
        // Check ownership
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Ви не можете видалити цей коментар',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Коментар видалено',
        ]);
    }
}
