<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use Illuminate\Database\Seeder;

class WatchHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = User::all();
        $videos = Video::where('is_published', true)->get();

        if ($videos->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $profiles = $user->profiles()->get();

            if ($profiles->isEmpty()) {
                continue;
            }

            foreach ($profiles as $profile) {
                // Add 5-10 watch histories per profile
                $videosToWatch = $videos->random(min(rand(5, 10), $videos->count()));

                foreach ($videosToWatch as $video) {
                    // Skip if watch history already exists for this combination
                    $exists = WatchHistory::where([
                        'user_id' => $user->id,
                        'profile_id' => $profile->id,
                        'video_id' => $video->id,
                    ])->exists();

                    if ($exists) {
                        continue;
                    }

                    // 70% chance of completed watch, 30% in progress
                    if (rand(0, 100) < 70) {
                        WatchHistory::factory()
                            ->completed()
                            ->create([
                                'user_id' => $user->id,
                                'profile_id' => $profile->id,
                                'video_id' => $video->id,
                                'total_seconds' => $video->duration_seconds ?? 1800,
                            ]);
                    } else {
                        WatchHistory::factory()
                            ->inProgress()
                            ->create([
                                'user_id' => $user->id,
                                'profile_id' => $profile->id,
                                'video_id' => $video->id,
                                'total_seconds' => $video->duration_seconds ?? 1800,
                            ]);
                    }
                }
            }
        }
    }
}
