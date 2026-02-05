<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Story
 */
class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'status' => $this->status,
            'mode' => $this->mode,
            'rating' => $this->rating,
            'is_public' => $this->is_public,
            'word_count' => $this->word_count,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,

            // Relationships
            'author' => new UserResource($this->whenLoaded('author')),
            'universe' => new UniverseResource($this->whenLoaded('universe')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'characters' => CharacterResource::collection($this->whenLoaded('characters')),

            // Counts
            'chapters_count' => $this->whenCounted('chapters'),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
        ];
    }
}
