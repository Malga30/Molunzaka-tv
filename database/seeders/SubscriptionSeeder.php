<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = User::all();
        $plans = Plan::where('is_active', true)->get();

        foreach ($users as $user) {
            // Each user gets a random active subscription
            $plan = $plans->random();
            Subscription::factory()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'started_at' => now()->subMonths(rand(1, 12)),
                'ends_at' => now()->addMonths(rand(1, 12)),
                'status' => 'active',
                'price_paid' => $plan->price,
            ]);

            // Randomly add cancelled subscriptions
            if (rand(0, 1)) {
                $oldPlan = $plans->random();
                Subscription::factory()->cancelled()->create([
                    'user_id' => $user->id,
                    'plan_id' => $oldPlan->id,
                    'price_paid' => $oldPlan->price,
                ]);
            }
        }
    }
}
