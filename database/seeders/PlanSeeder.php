<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Plan::factory()->create([
            'name' => 'Basic',
            'description' => 'Access to standard quality content.',
            'price' => 5.99,
            'max_profiles' => 1,
            'max_concurrent_streams' => 1,
            'hd_support' => false,
            'uhd_support' => false,
            'offline_download' => false,
            'ad_free' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Plan::factory()->create([
            'name' => 'Standard',
            'description' => 'HD quality content with multiple profiles.',
            'price' => 9.99,
            'max_profiles' => 2,
            'max_concurrent_streams' => 2,
            'hd_support' => true,
            'uhd_support' => false,
            'offline_download' => true,
            'ad_free' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Plan::factory()->create([
            'name' => 'Premium',
            'description' => 'Premium 4K experience with all features.',
            'price' => 15.99,
            'max_profiles' => 4,
            'max_concurrent_streams' => 4,
            'hd_support' => true,
            'uhd_support' => true,
            'offline_download' => true,
            'ad_free' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Plan::factory()->count(2)->inactive()->create();
    }
}
