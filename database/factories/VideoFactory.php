<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'description' => $this->faker->paragraphs(3, true),
            'slug' => Str::slug($title),
            'thumbnail_url' => $this->faker->imageUrl(320, 180),
            'poster_url' => $this->faker->imageUrl(400, 600),
            'content_type' => $this->faker->randomElement(['video', 'movie', 'series', 'documentary']),
            'rating' => $this->faker->randomElement(['G', 'PG', 'PG-13', 'R', 'NC-17']),
            'duration_seconds' => $this->faker->numberBetween(600, 7200),
            'views_count' => $this->faker->numberBetween(0, 100000),
            'rating_score' => $this->faker->randomFloat(2, 0, 10),
            'rating_count' => $this->faker->numberBetween(0, 10000),
            'is_published' => $this->faker->boolean(70),
            'is_featured' => $this->faker->boolean(20),
            'published_at' => $this->faker->dateTimeBetween('-1 year'),
            'release_date' => $this->faker->date(),
            'genres' => $this->faker->randomElements(['Action', 'Drama', 'Comedy', 'Horror', 'Sci-Fi', 'Romance'], 2),
            'cast' => $this->faker->randomElements(
                [$this->faker->name(), $this->faker->name(), $this->faker->name()],
                $this->faker->numberBetween(1, 3)
            ),
            'metadata' => [
                'language' => 'en',
                'subtitles' => ['en', 'es', 'fr'],
            ],
        ];
    }

    /**
     * Mark video as published.
     *
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Mark video as featured.
     *
     * @return static
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Unpublished video.
     *
     * @return static
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
