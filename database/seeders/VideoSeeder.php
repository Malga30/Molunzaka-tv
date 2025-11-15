<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $creators = User::where('role', 'creator')->get();

        if ($creators->isEmpty()) {
            // Create creators if none exist
            $creators = User::factory()->creator()->count(3)->create();
        }

        foreach ($creators as $creator) {
            // Create published videos
            Video::factory()
                ->published()
                ->count(rand(3, 5))
                ->create(['user_id' => $creator->id]);

            // Create featured videos
            Video::factory()
                ->published()
                ->featured()
                ->count(rand(1, 2))
                ->create(['user_id' => $creator->id]);

            // Create unpublished videos
            Video::factory()
                ->unpublished()
                ->count(rand(1, 3))
                ->create(['user_id' => $creator->id]);
        }

        // Create videos for regular users too
        $users = User::where('role', 'user')->limit(5)->get();
        foreach ($users as $user) {
            Video::factory()
                ->published()
                ->count(rand(1, 2))
                ->create(['user_id' => $user->id]);
        }
    }
}
