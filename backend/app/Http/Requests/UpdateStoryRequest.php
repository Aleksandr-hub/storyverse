<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $story = $this->route('story');
        return $story && $this->user()->id === $story->author_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover_url' => ['nullable', 'url', 'max:500'],
            'universe_id' => ['nullable', 'uuid', 'exists:universes,id'],
            'status' => ['sometimes', 'string', 'in:draft,published,completed'],
            'is_public' => ['sometimes', 'boolean'],
            'rating' => ['sometimes', 'string', 'in:0+,6+,12+,16+,18+'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
