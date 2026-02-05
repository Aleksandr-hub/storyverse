<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Illustration
 */
class IllustrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_url,
            'prompt' => $this->prompt,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
