<?php

namespace Tests\Unit\Models;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * Test user can be created.
     *
     * @return void
     */
    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
    }

    /**
     * Test user has profiles relationship.
     *
     * @return void
     */
    public function test_user_has_profiles(): void
    {
        $user = User::factory()
            ->has(Profile::factory()->count(2))
            ->create();

        $this->assertCount(2, $user->profiles);
    }

    /**
     * Test user primary profile.
     *
     * @return void
     */
    public function test_user_primary_profile(): void
    {
        $user = User::factory()->create();
        $primaryProfile = Profile::factory()->primary()->create(['user_id' => $user->id]);
        Profile::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($primaryProfile->id, $user->primaryProfile()->id);
    }

    /**
     * Test user active subscription.
     *
     * @return void
     */
    public function test_user_active_subscription(): void
    {
        $user = User::factory()
            ->has(\App\Models\Subscription::factory()->create(['status' => 'active']))
            ->create();

        $activeSubscription = $user->activeSubscription();
        $this->assertNotNull($activeSubscription);
        $this->assertEquals('active', $activeSubscription->status);
    }

    /**
     * Test user is creator.
     *
     * @return void
     */
    public function test_user_is_creator(): void
    {
        $creator = User::factory()->creator()->create();
        $regularUser = User::factory()->create();

        $this->assertTrue($creator->isCreator());
        $this->assertFalse($regularUser->isCreator());
    }

    /**
     * Test user is admin.
     *
     * @return void
     */
    public function test_user_is_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $regularUser = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }
}
