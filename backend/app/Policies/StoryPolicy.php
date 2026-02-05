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
        // Public stories can be viewed by anyone
        if ($story->is_public) {
            return true;
        }

        // Private stories - only owner can view
        return $user && $user->id === $story->author_id;
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
