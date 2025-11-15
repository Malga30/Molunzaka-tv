<?php

namespace Database\Seeders;

use App\Models\Video;
use App\Models\VideoFile;
use Illuminate\Database\Seeder;

class VideoFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $videos = Video::all();

        foreach ($videos as $video) {
            // Create 1-2 video files per video
            VideoFile::factory()
                ->count(rand(1, 2))
                ->create(['video_id' => $video->id]);
        }
    }
}
