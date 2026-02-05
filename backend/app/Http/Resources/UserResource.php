<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->when($this->bio !== null, $this->bio),
            'is_premium' => $this->when($request->user()?->id === $this->id, $this->is_premium),
            'created_at' => $this->created_at,
        ];
    }
}
