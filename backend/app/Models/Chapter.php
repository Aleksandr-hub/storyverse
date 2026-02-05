<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'story_id',
        'title',
        'content',
        'chapter_number',
        'word_count',
        'author_id',
        'is_ai_generated',
    ];

    protected $casts = [
        'chapter_number' => 'integer',
        'word_count' => 'integer',
        'is_ai_generated' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Chapter $chapter) {
            $chapter->word_count = self::countWords($chapter->content ?? '');
        });

        static::saved(function (Chapter $chapter) {
            $chapter->story?->updateWordCount();
        });

        static::deleted(function (Chapter $chapter) {
            $chapter->story?->updateWordCount();
        });
    }

    /**
     * Count words in text (supports Ukrainian/Cyrillic).
     */
    public static function countWords(string $text): int
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove markdown formatting
        $text = preg_replace('/[*_~`#\[\]()>-]+/', ' ', $text);

        // Match words (Latin + Cyrillic + numbers + apostrophes for Ukrainian)
        preg_match_all('/[\p{L}\p{N}\']+/u', $text, $matches);

        return count($matches[0]);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function illustrations(): HasMany
    {
        return $this->hasMany(Illustration::class)->orderBy('position');
    }
}
