<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    /**
     * Test subscription can be created with valid data.
     *
     * @return void
     */
    public function test_subscription_can_be_created(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Test subscription relationships.
     *
     * @return void
     */
    public function test_subscription_relationships(): void
    {
        $subscription = Subscription::factory()
            ->for(User::factory())
            ->for(Plan::factory())
            ->create();

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertInstanceOf(Plan::class, $subscription->plan);
    }

    /**
     * Test subscription is active check.
     *
     * @return void
     */
    public function test_subscription_is_active(): void
    {
        $activeSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(30),
        ]);

        $expiredSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDays(10),
        ]);

        $this->assertTrue($activeSubscription->isActive());
        $this->assertFalse($expiredSubscription->isActive());
    }

    /**
     * Test subscription can be renewed.
     *
     * @return void
     */
    public function test_subscription_can_renew(): void
    {
        $renewableSubscription = Subscription::factory()->create([
            'auto_renew' => true,
            'status' => 'active',
        ]);

        $nonRenewableSubscription = Subscription::factory()->create([
            'auto_renew' => false,
            'status' => 'active',
        ]);

        $cancelledSubscription = Subscription::factory()->cancelled()->create();

        $this->assertTrue($renewableSubscription->canRenew());
        $this->assertFalse($nonRenewableSubscription->canRenew());
        $this->assertFalse($cancelledSubscription->canRenew());
    }
}
