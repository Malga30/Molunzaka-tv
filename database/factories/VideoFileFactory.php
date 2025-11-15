<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\VideoFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoFile>
 */
class VideoFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'filename' => $this->faker->fileName('videos'),
            'storage_path' => 'videos/' . $this->faker->sha1() . '.mp4',
            'mime_type' => 'video/mp4',
            'file_size_bytes' => $this->faker->numberBetween(100000000, 2000000000),
            'duration_seconds' => $this->faker->numberBetween(600, 7200),
            'width' => $this->faker->randomElement([1920, 1280, 854, 640]),
            'height' => $this->faker->randomElement([1080, 720, 480, 360]),
            'fps' => $this->faker->randomElement([23.976, 24, 29.97, 30, 60]),
            'codec_video' => 'h264',
            'codec_audio' => 'aac',
            'bitrate_kbps' => $this->faker->numberBetween(1000, 8000),
            'status' => 'completed',
            'progress_percent' => 100,
            'processing_completed_at' => now(),
        ];
    }

    /**
     * Mark file as processing.
     *
     * @return static
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'progress_percent' => $this->faker->numberBetween(0, 99),
            'processing_started_at' => now()->subHours(1),
        ]);
    }

    /**
     * Mark file as failed.
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Processing failed: Invalid codec',
        ]);
    }
}
