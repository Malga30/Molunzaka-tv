<?php

namespace Tests\Unit\Models;

use App\Models\Profile;
use App\Models\WatchHistory;
use Tests\TestCase;

class WatchHistoryTest extends TestCase
{
    /**
     * Test watch history can be created.
     *
     * @return void
     */
    public function test_watch_history_can_be_created(): void
    {
        $watchHistory = WatchHistory::factory()->create();

        $this->assertDatabaseHas('watch_histories', [
            'user_id' => $watchHistory->user_id,
            'video_id' => $watchHistory->video_id,
        ]);
    }

    /**
     * Test watch history relationships.
     *
     * @return void
     */
    public function test_watch_history_relationships(): void
    {
        $watchHistory = WatchHistory::factory()->create();

        $this->assertNotNull($watchHistory->user);
        $this->assertNotNull($watchHistory->profile);
        $this->assertNotNull($watchHistory->video);
    }

    /**
     * Test watch history progress update.
     *
     * @return void
     */
    public function test_watch_history_update_progress(): void
    {
        $watchHistory = WatchHistory::factory()->create([
            'watched_seconds' => 500,
            'total_seconds' => 1000,
            'progress_percent' => 50,
        ]);

        $watchHistory->watched_seconds = 900;
        $watchHistory->updateProgress();
        $watchHistory->refresh();

        $this->assertEquals(90, $watchHistory->progress_percent);
        $this->assertTrue($watchHistory->is_completed);
    }

    /**
     * Test watch history unique constraint.
     *
     * @return void
     */
    public function test_watch_history_unique_per_profile(): void
    {
        $watchHistory = WatchHistory::factory()->create();

        // Attempting to create a duplicate should fail
        $this->assertThrows(function () use ($watchHistory) {
            WatchHistory::create([
                'user_id' => $watchHistory->user_id,
                'profile_id' => $watchHistory->profile_id,
                'video_id' => $watchHistory->video_id,
                'watched_seconds' => 100,
                'total_seconds' => 1000,
                'progress_percent' => 10,
                'quality_watched' => '720p',
                'started_at' => now(),
                'last_watched_at' => now(),
            ]);
        });
    }
}
