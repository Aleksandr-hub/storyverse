<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_url',
        'universe_id',
        'author_id',
        'status',
        'mode',
        'is_public',
        'rating',
        'word_count',
        'view_count',
        'like_count',
        'settings',
        'published_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'settings' => 'array',
        'published_at' => 'datetime',
        'word_count' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function universe(): BelongsTo
    {
        return $this->belongsTo(Universe::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'story_tags');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'story_characters')
            ->withPivot('role');
    }

    public function illustrations(): HasMany
    {
        return $this->hasMany(Illustration::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('is_public', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function updateWordCount(): void
    {
        $this->word_count = $this->chapters()->sum('word_count');
        $this->save();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes');
    }
}
