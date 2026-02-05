<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'universe_id' => ['nullable', 'uuid', 'exists:universes,id'],
            'mode' => ['sometimes', 'string', 'in:story,collaborative,adventure'],
            'is_public' => ['sometimes', 'boolean'],
            'rating' => ['sometimes', 'string', 'in:0+,6+,12+,16+,18+'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],
        ];
    }
}
