<?php

namespace Database\Factories;

use App\Models\OmadaSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OmadaSetting>
 */
class OmadaSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'controller_url' => 'https://omada.example.com',
            'username' => 'admin',
            'password' => 'password',
            'api_key' => null,
            'hotspot_operator_name' => 'hotspot_operator',
            'hotspot_operator_password' => 'operator_password',
            'external_portal_url' => config('app.url') . '/portal',
            'site_id' => null,
            'omada_id' => null,
            'is_connected' => false,
            'last_synced_at' => null,
        ];
    }
}
