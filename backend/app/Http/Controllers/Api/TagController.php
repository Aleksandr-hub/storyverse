<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->withCount('stories');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }

        $tags = $query->orderBy('name')->get();

        return response()->json($tags);
    }

    public function show(Tag $tag): JsonResponse
    {
        $tag->loadCount('stories');

        return response()->json($tag);
    }

    public function popular(): JsonResponse
    {
        $tags = Tag::query()
            ->withCount('stories')
            ->orderBy('stories_count', 'desc')
            ->limit(20)
            ->get();

        return response()->json($tags);
    }
}
