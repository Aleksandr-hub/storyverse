<?php

namespace App\Http\Requests;

use App\Models\Chapter;
use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $story = $this->route('story');

        return $story && $this->user()->can('create', [Chapter::class, $story]);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
        ];
    }
}
