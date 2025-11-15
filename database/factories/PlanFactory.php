<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ['Basic', 'Standard', 'Premium', 'Pro', 'Family'][rand(0, 4)];

        return [
            'name' => $name,
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 20),
            'max_profiles' => $this->faker->numberBetween(1, 6),
            'max_concurrent_streams' => $this->faker->numberBetween(1, 4),
            'hd_support' => $this->faker->boolean(70),
            'uhd_support' => $this->faker->boolean(30),
            'offline_download' => $this->faker->boolean(50),
            'ad_free' => $this->faker->boolean(80),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Mark plan as inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
