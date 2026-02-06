<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// TODO: Add MustVerifyEmail when email verification is configured
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'username',
        'password',
        'avatar_url',
        'bio',
        'oauth_provider',
        'oauth_id',
        'is_premium',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'oauth_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user registered via OAuth.
     */
    public function isOAuthUser(): bool
    {
        return ! empty($this->oauth_provider);
    }

    /**
     * Check if user has set a password.
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    /**
     * Get user's display name (username or email prefix).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->username ?? explode('@', $this->email)[0];
    }

    /**
     * Get avatar URL with fallback to default.
     */
    public function getAvatarAttribute(): string
    {
        return $this->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($this->display_name).'&background=random';
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class, 'author_id');
    }

    public function universes(): HasMany
    {
        return $this->hasMany(Universe::class, 'creator_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'creator_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likedStories()
    {
        return $this->belongsToMany(Story::class, 'likes');
    }
}
