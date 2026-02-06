<?php

namespace App\Services\Story;

use App\Models\Story;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Service for building story queries with filtering, sorting, and pagination.
 * Extracted from StoryController to follow Single Responsibility Principle.
 */
class StoryQueryService
{
    /**
     * Build and execute a query for listing stories.
     */
    public function getFilteredStories(Request $request): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery();

        $this->applyUserFilter($query, $request);
        $this->applyUniverseFilter($query, $request);
        $this->applyModeFilter($query, $request);
        $this->applyTagFilter($query, $request);
        $this->applySearchFilter($query, $request);
        $this->applySorting($query, $request);

        return $query->paginate($request->integer('per_page', 12));
    }

    /**
     * Get stories for authenticated user.
     */
    public function getMyStories(Request $request): LengthAwarePaginator
    {
        return Story::query()
            ->where('author_id', $request->user()->id)
            ->with(['universe:id,name,slug', 'tags:id,name,slug'])
            ->withCount('chapters')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->integer('per_page', 12));
    }

    /**
     * Build the base query with common eager loading.
     */
    private function buildBaseQuery(): Builder
    {
        return Story::query()
            ->with([
                'author:id,username,avatar_url',
                'universe:id,name,slug',
                'tags:id,name,slug',
            ])
            ->withCount('chapters');
    }

    /**
     * Apply user-based filtering.
     *
     * @param  Builder<Story>  $query
     */
    private function applyUserFilter(Builder $query, Request $request): void
    {
        if (! $request->has('user')) {
            $query->published();

            return;
        }

        if ($request->user() && $request->string('user')->toString() === 'me') {
            $query->where('author_id', $request->user()->id);
        } else {
            $query->where('author_id', $request->string('user')->toString())
                ->where('is_public', true)
                ->where('status', 'published');
        }
    }

    /**
     * Apply universe filter.
     */
    private function applyUniverseFilter(Builder $query, Request $request): void
    {
        if ($request->has('universe')) {
            $query->where('universe_id', $request->string('universe')->toString());
        }
    }

    /**
     * Apply mode filter.
     */
    private function applyModeFilter(Builder $query, Request $request): void
    {
        if ($request->has('mode')) {
            $query->where('mode', $request->string('mode')->toString());
        }
    }

    /**
     * Apply tag filter.
     */
    private function applyTagFilter(Builder $query, Request $request): void
    {
        if ($request->has('tag')) {
            $tagId = $request->string('tag')->toString();
            $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tagId));
        }
    }

    /**
     * Apply search filter with proper escaping.
     */
    private function applySearchFilter(Builder $query, Request $request): void
    {
        if (! $request->has('search')) {
            return;
        }

        $search = $request->string('search')->toString();

        // Escape special characters for LIKE pattern
        $search = str_replace(['%', '_'], ['\%', '\_'], $search);

        $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    /**
     * Apply sorting.
     */
    private function applySorting(Builder $query, Request $request): void
    {
        $allowedSortFields = ['created_at', 'updated_at', 'title', 'view_count', 'like_count'];
        $sortField = $request->string('sort', 'created_at')->toString();
        $sortDirection = $request->string('direction', 'desc')->toString();

        // Validate sort field to prevent SQL injection
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortDirection);
    }
}
