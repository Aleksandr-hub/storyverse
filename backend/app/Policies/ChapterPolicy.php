<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\Story;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChapterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any chapters of the story.
     */
    public function viewAny(?User $user, Story $story): bool
    {
        if ($story->is_public) {
            return true;
        }

        return $user && $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can view the chapter.
     */
    public function view(?User $user, Chapter $chapter): bool
    {
        $story = $chapter->story;

        if ($story->is_public) {
            return true;
        }

        return $user && $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can create chapters for the story.
     */
    public function create(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can reorder chapters of the story.
     */
    public function reorder(User $user, Story $story): bool
    {
        return $user->id === $story->author_id;
    }

    /**
     * Determine whether the user can update the chapter.
     */
    public function update(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->story->author_id;
    }

    /**
     * Determine whether the user can delete the chapter.
     */
    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->story->author_id;
    }
}
