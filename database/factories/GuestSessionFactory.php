<?php

namespace Database\Factories;

use App\Models\GuestSession;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestSession>
 */
class GuestSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = $this->faker->dateTimeBetween('-24 hours', 'now');
        $status = $this->faker->randomElement(['active', 'expired', 'disconnected']);

        return [
            'client_mac' => strtoupper($this->faker->unique()->macAddress()),
            'ap_mac' => strtoupper($this->faker->macAddress()),
            'ip_address' => $this->faker->localIpv4(),
            'ssid' => $this->faker->randomElement(['FreeWiFi', 'HotspotZone', 'GuestNet', 'SKY-WiFi']),
            'username' => $this->faker->optional(0.3)->phoneNumber(),
            'plan_id' => Plan::factory(),
            'data_used_mb' => $this->faker->randomFloat(2, 0, 2048),
            'data_limit_mb' => $this->faker->optional(0.5)->randomFloat(2, 500, 10240),
            'time_started' => $started,
            'time_expires' => $this->faker->dateTimeBetween($started, '+48 hours'),
            'time_ended' => $status !== 'active' ? $this->faker->dateTimeBetween($started, 'now') : null,
            'status' => $status,
            'omada_auth_id' => $this->faker->optional()->uuid(),
        ];
    }

    /**
     * State: active session.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'time_ended' => null,
            'time_started' => now()->subMinutes(rand(5, 120)),
            'time_expires' => now()->addHours(rand(1, 24)),
        ]);
    }

    /**
     * State: expired session.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'time_started' => now()->subHours(rand(2, 12)),
            'time_expires' => now()->subMinutes(rand(1, 60)),
            'time_ended' => now()->subMinutes(rand(1, 60)),
        ]);
    }
}
