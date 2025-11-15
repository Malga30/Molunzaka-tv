<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchHistory>
 */
class WatchHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalSeconds = $this->faker->numberBetween(600, 7200);
        $watchedSeconds = $this->faker->numberBetween(0, $totalSeconds);
        $progressPercent = ($watchedSeconds / $totalSeconds) * 100;

        return [
            'user_id' => User::factory(),
            'profile_id' => Profile::factory(),
            'video_id' => Video::factory(),
            'watched_seconds' => $watchedSeconds,
            'total_seconds' => $totalSeconds,
            'progress_percent' => $progressPercent,
            'quality_watched' => $this->faker->randomElement(['360p', '480p', '720p', '1080p']),
            'device_type' => $this->faker->randomElement(['web', 'mobile', 'tablet', 'tv']),
            'device_os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'started_at' => now()->subDays($this->faker->numberBetween(0, 30)),
            'last_watched_at' => now(),
            'finished_at' => $progressPercent >= 90 ? now() : null,
            'is_completed' => $progressPercent >= 90,
        ];
    }

    /**
     * Mark watch history as completed.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'watched_seconds' => $attributes['total_seconds'],
            'progress_percent' => 100,
            'is_completed' => true,
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark watch history as in progress.
     *
     * @return static
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'watched_seconds' => intval($attributes['total_seconds'] * 0.5),
            'progress_percent' => 50,
            'is_completed' => false,
            'finished_at' => null,
        ]);
    }
}
