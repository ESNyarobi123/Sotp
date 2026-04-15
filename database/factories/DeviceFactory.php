<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Lobby AP', 'Reception AP', 'Conference Room', 'Rooftop Bar',
                'Pool Area', 'Restaurant', 'Main Entrance', 'Floor 1 AP',
                'Floor 2 AP', 'Garden Terrace',
            ]) . ' - ' . $this->faker->company(),
            'ap_mac' => strtoupper($this->faker->unique()->macAddress()),
            'omada_device_id' => $this->faker->optional(0.7)->uuid(),
            'site_name' => $this->faker->randomElement(['Main Branch', 'Downtown Office', 'Beach Resort', 'Airport Lounge']),
            'ip_address' => $this->faker->localIpv4(),
            'model' => $this->faker->randomElement(['EAP620 HD', 'EAP670', 'EAP610', 'EAP225', 'EAP245']),
            'firmware_version' => $this->faker->numerify('#.#.#'),
            'clients_count' => $this->faker->numberBetween(0, 30),
            'uptime_seconds' => $this->faker->numberBetween(0, 604800),
            'channel_2g' => (string) $this->faker->randomElement([1, 6, 11]),
            'channel_5g' => (string) $this->faker->randomElement([36, 40, 44, 48, 149, 153]),
            'tx_power_2g' => $this->faker->numberBetween(10, 25),
            'tx_power_5g' => $this->faker->numberBetween(15, 30),
            'status' => $this->faker->randomElement(['online', 'offline', 'unknown']),
            'last_seen_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * State: online device.
     */
    public function online(): static
    {
        return $this->state(fn () => [
            'status' => 'online',
            'clients_count' => rand(1, 25),
            'uptime_seconds' => rand(3600, 604800),
            'last_seen_at' => now()->subMinutes(rand(1, 5)),
        ]);
    }

    /**
     * State: offline device.
     */
    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => 'offline',
            'last_seen_at' => now()->subHours(rand(1, 48)),
        ]);
    }
}
