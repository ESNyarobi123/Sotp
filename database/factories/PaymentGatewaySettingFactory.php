<?php

namespace Database\Factories;

use App\Models\PaymentGatewaySetting;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGatewaySetting>
 */
class PaymentGatewaySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gateway = $this->faker->unique()->randomElement(['mpesa', 'airtel', 'tigo', 'card']);

        return [
            'workspace_id' => Workspace::factory(),
            'gateway' => $gateway,
            'display_name' => match ($gateway) {
                'mpesa' => 'M-Pesa',
                'airtel' => 'Airtel Money',
                'tigo' => 'Tigo Pesa',
                'card' => 'Card Payment',
            },
            'is_active' => $gateway === 'mpesa',
            'config' => match ($gateway) {
                'mpesa' => [
                    'consumer_key' => 'your_consumer_key',
                    'consumer_secret' => 'your_consumer_secret',
                    'shortcode' => '174379',
                    'passkey' => 'your_passkey',
                    'callback_url' => config('app.url').'/api/mpesa/callback',
                    'environment' => 'sandbox',
                ],
                default => [],
            },
        ];
    }
}
