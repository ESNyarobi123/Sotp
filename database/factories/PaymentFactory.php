<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed', 'failed', 'cancelled']);
        $method = $this->faker->randomElement(['mpesa', 'airtel', 'tigo']);

        return [
            'transaction_id' => strtoupper(Str::random(12)),
            'phone_number' => '+255' . $this->faker->numerify('#########'),
            'amount' => $this->faker->randomElement([500, 1000, 2000, 3000, 5000, 10000]),
            'currency' => 'TZS',
            'payment_method' => $method,
            'status' => $status,
            'plan_id' => Plan::factory(),
            'guest_session_id' => null,
            'client_mac' => strtoupper($this->faker->macAddress()),
            'ap_mac' => strtoupper($this->faker->macAddress()),
            'mpesa_checkout_request_id' => $method === 'mpesa' ? 'ws_CO_' . $this->faker->numerify('##############') : null,
            'mpesa_receipt_number' => $status === 'completed' && $method === 'mpesa' ? strtoupper(Str::random(10)) : null,
            'metadata' => null,
            'paid_at' => $status === 'completed' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    /**
     * State: completed payment.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'paid_at' => now()->subMinutes(rand(1, 1440)),
        ]);
    }
}
