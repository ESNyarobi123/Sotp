<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Workspace;
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
        $type = $this->faker->randomElement(['time', 'data', 'unlimited']);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => match ($type) {
                'time' => $this->faker->randomElement(['1 Hour', '3 Hours', '12 Hours', '24 Hours', '3 Days', '7 Days']),
                'data' => $this->faker->randomElement(['500 MB', '1 GB', '3 GB', '5 GB', '10 GB']),
                'unlimited' => $this->faker->randomElement(['Unlimited 1 Hour', 'Unlimited 24 Hours', 'Unlimited 7 Days']),
            },
            'type' => $type,
            'value' => match ($type) {
                'time' => $this->faker->randomElement([60, 180, 720, 1440, 4320, 10080]),
                'data' => $this->faker->randomElement([500, 1024, 3072, 5120, 10240]),
                'unlimited' => null,
            },
            'duration_minutes' => $type === 'unlimited'
                ? $this->faker->randomElement([60, 1440, 10080])
                : null,
            'price' => $this->faker->randomElement([500, 1000, 2000, 3000, 5000, 10000]),
            'validity_days' => $this->faker->randomElement([1, 3, 7, 30]),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
