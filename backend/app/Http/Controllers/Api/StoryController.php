<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoryRequest;
use App\Http\Requests\UpdateStoryRequest;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use App\Services\Story\StoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function __construct(
        private readonly StoryQueryService $queryService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $stories = $this->queryService->getFilteredStories($request);

        return StoryResource::collection($stories);
    }

    public function store(StoreStoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['title']).'-'.Str::random(8);

        $story = Story::create($data);

        if (isset($data['tags'])) {
            $story->tags()->sync($data['tags']);
        }

        $story->load(['author:id,username,avatar_url', 'universe:id,name,slug', 'tags:id,name,slug']);

        return response()->json([
            'message' => 'Історію створено',
            'story' => new StoryResource($story),
        ], 201);
    }

    public function show(Request $request, Story $story): JsonResponse
    {
        $this->authorize('view', $story);

        $story->load([
            'author:id,username,avatar_url,bio',
            'universe:id,name,slug,description',
            'tags:id,name,slug,category',
            'chapters:id,story_id,title,chapter_number,word_count,created_at',
            'characters:id,name,avatar_url',
        ]);

        if ($request->user()?->id !== $story->author_id) {
            $story->increment('view_count');
        }

        return response()->json(new StoryResource($story));
    }

    public function update(UpdateStoryRequest $request, Story $story): JsonResponse
    {
        // Authorization is handled in UpdateStoryRequest
        $data = $request->validated();

        if (isset($data['title']) && $data['title'] !== $story->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::random(8);
        }

        if (isset($data['status']) && $data['status'] === 'published' && $story->status !== 'published') {
            $data['published_at'] = now();
        }

        $story->update($data);

        if (isset($data['tags'])) {
            $story->tags()->sync($data['tags']);
        }

        $story->load(['author:id,username,avatar_url', 'universe:id,name,slug', 'tags:id,name,slug']);

        return response()->json([
            'message' => 'Історію оновлено',
            'story' => new StoryResource($story),
        ]);
    }

    public function destroy(Request $request, Story $story): JsonResponse
    {
        $this->authorize('delete', $story);

        $story->delete();

        return response()->json(['message' => 'Історію видалено']);
    }

    public function publish(Request $request, Story $story): JsonResponse
    {
        $this->authorize('publish', $story);

        $story->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Історію опубліковано',
            'story' => new StoryResource($story),
        ]);
    }

    public function myStories(Request $request): AnonymousResourceCollection
    {
        $stories = $this->queryService->getMyStories($request);

        return StoryResource::collection($stories);
    }
}
