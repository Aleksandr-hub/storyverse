<?php

namespace Tests\Unit\Policies;

use App\Models\Chapter;
use App\Models\Story;
use App\Models\User;
use App\Policies\ChapterPolicy;
use PHPUnit\Framework\TestCase;

class ChapterPolicyTest extends TestCase
{
    private ChapterPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ChapterPolicy();
    }

    public function test_view_any_allows_for_public_story(): void
    {
        $story = $this->createMockStory(isPublic: true);

        $result = $this->policy->viewAny(null, $story);

        $this->assertTrue($result);
    }

    public function test_view_any_denies_guest_for_private_story(): void
    {
        $story = $this->createMockStory(isPublic: false);

        $result = $this->policy->viewAny(null, $story);

        $this->assertFalse($result);
    }

    public function test_view_any_allows_owner_for_private_story(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(isPublic: false, authorId: $userId);

        $result = $this->policy->viewAny($user, $story);

        $this->assertTrue($result);
    }

    public function test_view_allows_for_public_story(): void
    {
        $story = $this->createMockStory(isPublic: true);
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->view(null, $chapter);

        $this->assertTrue($result);
    }

    public function test_view_denies_guest_for_private_story(): void
    {
        $story = $this->createMockStory(isPublic: false);
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->view(null, $chapter);

        $this->assertFalse($result);
    }

    public function test_view_allows_owner_for_private_story(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(isPublic: false, authorId: $userId);
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->view($user, $chapter);

        $this->assertTrue($result);
    }

    public function test_create_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->create($user, $story);

        $this->assertTrue($result);
    }

    public function test_create_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->create($user, $story);

        $this->assertFalse($result);
    }

    public function test_update_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->update($user, $chapter);

        $this->assertTrue($result);
    }

    public function test_update_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->update($user, $chapter);

        $this->assertFalse($result);
    }

    public function test_delete_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->delete($user, $chapter);

        $this->assertTrue($result);
    }

    public function test_delete_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');
        $chapter = $this->createMockChapter($story);

        $result = $this->policy->delete($user, $chapter);

        $this->assertFalse($result);
    }

    public function test_reorder_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->reorder($user, $story);

        $this->assertTrue($result);
    }

    public function test_reorder_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->reorder($user, $story);

        $this->assertFalse($result);
    }

    private function createMockUser(string $id): User
    {
        $user = $this->createMock(User::class);
        $user->id = $id;
        return $user;
    }

    private function createMockStory(bool $isPublic = true, string $authorId = 'user-123'): Story
    {
        $story = $this->createMock(Story::class);
        $story->is_public = $isPublic;
        $story->author_id = $authorId;
        return $story;
    }

    private function createMockChapter(Story $story): Chapter
    {
        $chapter = $this->createMock(Chapter::class);
        $chapter->story = $story;
        return $chapter;
    }
}
