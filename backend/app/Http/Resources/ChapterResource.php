<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Chapter
 */
class ChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'story_id' => $this->story_id,
            'title' => $this->title,
            'content' => $this->when($this->relationLoaded('story') || $request->routeIs('chapters.*'), $this->content),
            'chapter_number' => $this->chapter_number,
            'word_count' => $this->word_count,
            'is_ai_generated' => $this->is_ai_generated,
            'author' => new UserResource($this->whenLoaded('author')),
            'illustrations' => IllustrationResource::collection($this->whenLoaded('illustrations')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
