<?php

namespace Database\Factories;

use App\Models\VideoFile;
use App\Models\VideoRendition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoRendition>
 */
class VideoRenditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $renditions = [
            ['name' => '360p', 'height' => 360, 'width' => 640, 'bitrate' => 1000],
            ['name' => '480p', 'height' => 480, 'width' => 854, 'bitrate' => 2500],
            ['name' => '720p', 'height' => 720, 'width' => 1280, 'bitrate' => 5000],
            ['name' => '1080p', 'height' => 1080, 'width' => 1920, 'bitrate' => 8000],
        ];
        $rendition = $this->faker->randomElement($renditions);

        return [
            'video_file_id' => VideoFile::factory(),
            'name' => $rendition['name'],
            'height' => $rendition['height'],
            'width' => $rendition['width'],
            'bitrate_kbps' => $rendition['bitrate'],
            'codec_video' => 'h264',
            'codec_audio' => 'aac',
            'format' => 'mp4',
            'storage_path' => 'renditions/' . $this->faker->sha1() . '.mp4',
            'file_size_bytes' => $this->faker->numberBetween(50000000, 500000000),
            'status' => 'completed',
            'progress_percent' => 100,
            'processing_completed_at' => now(),
        ];
    }

    /**
     * Create a 720p rendition.
     *
     * @return static
     */
    public function hd(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '720p',
            'height' => 720,
            'width' => 1280,
            'bitrate_kbps' => 5000,
        ]);
    }

    /**
     * Create a 1080p rendition.
     *
     * @return static
     */
    public function fullHd(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '1080p',
            'height' => 1080,
            'width' => 1920,
            'bitrate_kbps' => 8000,
        ]);
    }
}
