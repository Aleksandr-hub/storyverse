<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the story.
     */
    public function view(?User $user, Story $story): bool
    {
        // Owner can always view their own stories
        if ($user && $user->id === $story->author_id) {
            return true;
        }

        // Public published stories can be viewed by anyone
        if ($story->is_public && $story->status === 'published') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the story.
     */
    public function update(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can delete the story.
     */
    public function delete(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can publish the story.
     */
    public function publish(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can manage chapters.
     */
    public function manageChapters(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }
}
