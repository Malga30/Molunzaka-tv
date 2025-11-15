<?php

namespace Database\Seeders;

use App\Models\VideoFile;
use App\Models\VideoRendition;
use Illuminate\Database\Seeder;

class VideoRenditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $videoFiles = VideoFile::all();

        $renditions = [
            ['name' => '360p', 'height' => 360, 'width' => 640, 'bitrate' => 1000],
            ['name' => '480p', 'height' => 480, 'width' => 854, 'bitrate' => 2500],
            ['name' => '720p', 'height' => 720, 'width' => 1280, 'bitrate' => 5000],
            ['name' => '1080p', 'height' => 1080, 'width' => 1920, 'bitrate' => 8000],
        ];

        foreach ($videoFiles as $videoFile) {
            // Create renditions for each video file
            // Randomly choose which renditions to create (at least 2)
            $selectedRenditions = array_slice(
                $renditions,
                0,
                rand(2, 4)
            );

            foreach ($selectedRenditions as $rendition) {
                VideoRendition::factory()->create([
                    'video_file_id' => $videoFile->id,
                    'name' => $rendition['name'],
                    'height' => $rendition['height'],
                    'width' => $rendition['width'],
                    'bitrate_kbps' => $rendition['bitrate'],
                ]);
            }
        }
    }
}
