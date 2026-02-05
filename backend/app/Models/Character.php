<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Character extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'universe_id',
        'name',
        'description',
        'avatar_url',
        'traits',
        'is_canonical',
        'creator_id',
    ];

    protected $casts = [
        'traits' => 'array',
        'is_canonical' => 'boolean',
    ];

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class, 'story_characters')
            ->withPivot('role');
    }
}
