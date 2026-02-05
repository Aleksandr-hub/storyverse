<?php

namespace Tests\Unit\Policies;

use App\Models\Story;
use App\Models\User;
use App\Policies\StoryPolicy;
use PHPUnit\Framework\TestCase;

class StoryPolicyTest extends TestCase
{
    private StoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StoryPolicy();
    }

    public function test_view_allows_anyone_for_public_published_story(): void
    {
        $story = $this->createMockStory(isPublic: true, status: 'published');

        $result = $this->policy->view(null, $story);

        $this->assertTrue($result);
    }

    public function test_view_denies_guest_for_private_story(): void
    {
        $story = $this->createMockStory(isPublic: false, status: 'published');

        $result = $this->policy->view(null, $story);

        $this->assertFalse($result);
    }

    public function test_view_denies_guest_for_draft_story(): void
    {
        $story = $this->createMockStory(isPublic: true, status: 'draft');

        $result = $this->policy->view(null, $story);

        $this->assertFalse($result);
    }

    public function test_view_allows_owner_for_any_story(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(isPublic: false, status: 'draft', authorId: $userId);

        $result = $this->policy->view($user, $story);

        $this->assertTrue($result);
    }

    public function test_view_denies_other_user_for_private_story(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(isPublic: false, status: 'published', authorId: 'user-123');

        $result = $this->policy->view($user, $story);

        $this->assertFalse($result);
    }

    public function test_update_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->update($user, $story);

        $this->assertTrue($result);
    }

    public function test_update_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->update($user, $story);

        $this->assertFalse($result);
    }

    public function test_delete_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->delete($user, $story);

        $this->assertTrue($result);
    }

    public function test_delete_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->delete($user, $story);

        $this->assertFalse($result);
    }

    public function test_publish_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->publish($user, $story);

        $this->assertTrue($result);
    }

    public function test_publish_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->publish($user, $story);

        $this->assertFalse($result);
    }

    public function test_manage_chapters_allows_owner(): void
    {
        $userId = 'user-123';
        $user = $this->createMockUser($userId);
        $story = $this->createMockStory(authorId: $userId);

        $result = $this->policy->manageChapters($user, $story);

        $this->assertTrue($result);
    }

    public function test_manage_chapters_denies_non_owner(): void
    {
        $user = $this->createMockUser('user-456');
        $story = $this->createMockStory(authorId: 'user-123');

        $result = $this->policy->manageChapters($user, $story);

        $this->assertFalse($result);
    }

    private function createMockUser(string $id): User
    {
        $user = $this->createMock(User::class);
        $user->id = $id;
        return $user;
    }

    private function createMockStory(
        bool $isPublic = true,
        string $status = 'published',
        string $authorId = 'user-123'
    ): Story {
        $story = $this->createMock(Story::class);
        $story->is_public = $isPublic;
        $story->status = $status;
        $story->author_id = $authorId;
        return $story;
    }
}
