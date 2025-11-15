<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 year');
        $billingCycle = $this->faker->randomElement(['monthly', 'yearly']);
        $daysToAdd = $billingCycle === 'yearly' ? 365 : 30;

        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'started_at' => $startedAt,
            'ends_at' => $this->faker->dateTimeBetween($startedAt, '+1 year'),
            'cancelled_at' => null,
            'status' => 'active',
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'debit_card']),
            'stripe_subscription_id' => 'sub_' . $this->faker->sha1(),
            'price_paid' => $this->faker->randomFloat(2, 5, 20),
            'auto_renew' => $this->faker->boolean(85),
            'billing_cycle' => $billingCycle,
        ];
    }

    /**
     * Mark subscription as cancelled.
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark subscription as expired.
     *
     * @return static
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'ends_at' => now()->subDays(10),
        ]);
    }
}
