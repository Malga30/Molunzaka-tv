<?php

namespace Tests\Unit\Models;

use App\Models\Video;
use App\Models\VideoFile;
use App\Models\WatchHistory;
use Tests\TestCase;

class VideoTest extends TestCase
{
    /**
     * Test video can be created.
     *
     * @return void
     */
    public function test_video_can_be_created(): void
    {
        $video = Video::factory()->create();

        $this->assertDatabaseHas('videos', [
            'slug' => $video->slug,
        ]);
    }

    /**
     * Test video relationships.
     *
     * @return void
     */
    public function test_video_relationships(): void
    {
        $video = Video::factory()
            ->has(VideoFile::factory()->count(2))
            ->has(WatchHistory::factory()->count(3))
            ->create();

        $this->assertCount(2, $video->videoFiles);
        $this->assertCount(3, $video->watchHistories);
    }

    /**
     * Test video increment views.
     *
     * @return void
     */
    public function test_video_increment_views(): void
    {
        $video = Video::factory()->create(['views_count' => 0]);

        $video->incrementViews();
        $video->refresh();

        $this->assertEquals(1, $video->views_count);
    }

    /**
     * Test video add rating.
     *
     * @return void
     */
    public function test_video_add_rating(): void
    {
        $video = Video::factory()->create([
            'rating_score' => 8.0,
            'rating_count' => 100,
        ]);

        $video->addRating(9.0);
        $video->refresh();

        $this->assertEquals(101, $video->rating_count);
        $this->assertNotEquals(8.0, $video->rating_score);
    }

    /**
     * Test published video filter.
     *
     * @return void
     */
    public function test_published_video_filter(): void
    {
        Video::factory()->published()->count(3)->create();
        Video::factory()->unpublished()->count(2)->create();

        $publishedVideos = Video::where('is_published', true)->get();

        $this->assertCount(3, $publishedVideos);
    }
}
