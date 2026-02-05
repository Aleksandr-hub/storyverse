<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChapterController extends Controller
{
    public function index(Request $request, Story $story): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('view', $story);

        $chapters = $story->chapters()
            ->select(['id', 'story_id', 'title', 'chapter_number', 'word_count', 'created_at', 'updated_at'])
            ->orderBy('chapter_number')
            ->get();

        return ChapterResource::collection($chapters);
    }

    public function store(StoreChapterRequest $request, Story $story): JsonResponse
    {
        $lastChapterNumber = $story->chapters()->max('chapter_number') ?? 0;

        $chapter = $story->chapters()->create([
            'title' => $request->input('title'),
            'content' => $request->input('content', ''),
            'chapter_number' => $lastChapterNumber + 1,
            'author_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Главу створено',
            'chapter' => new ChapterResource($chapter),
        ], 201);
    }

    public function show(Story $story, Chapter $chapter): ChapterResource|JsonResponse
    {
        if ($chapter->story_id !== $story->id) {
            abort(404, 'Главу не знайдено');
        }

        $this->authorize('view', $chapter);

        $chapter->load('illustrations');

        return new ChapterResource($chapter);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $chapter->update($request->validated());

        return response()->json([
            'message' => 'Главу оновлено',
            'chapter' => new ChapterResource($chapter),
        ]);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorize('delete', $chapter);

        $storyId = $chapter->story_id;
        $deletedNumber = $chapter->chapter_number;
        $chapter->delete();

        Chapter::where('story_id', $storyId)
            ->where('chapter_number', '>', $deletedNumber)
            ->decrement('chapter_number');

        return response()->json(['message' => 'Главу видалено']);
    }

    public function reorder(Request $request, Story $story): JsonResponse
    {
        $this->authorize('manageChapters', $story);

        $request->validate([
            'chapters' => ['required', 'array'],
            'chapters.*.id' => ['required', 'uuid', 'exists:chapters,id'],
            'chapters.*.chapter_number' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($request->chapters as $chapterData) {
            Chapter::where('id', $chapterData['id'])
                ->where('story_id', $story->id)
                ->update(['chapter_number' => $chapterData['chapter_number']]);
        }

        return response()->json(['message' => 'Порядок глав оновлено']);
    }
}
