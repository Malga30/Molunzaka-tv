<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            UserSeeder::class,
            SubscriptionSeeder::class,
            ProfileSeeder::class,
            VideoSeeder::class,
            VideoFileSeeder::class,
            VideoRenditionSeeder::class,
            SubtitleSeeder::class,
            WatchHistorySeeder::class,
        ]);
    }
}
