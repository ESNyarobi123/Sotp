<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'guest']);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@skyomada.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole($adminRole);

        // Create predefined plans
        $plans = collect([
            ['name' => '30 Minutes', 'type' => 'time', 'value' => 30, 'price' => 500, 'sort_order' => 1],
            ['name' => '1 Hour', 'type' => 'time', 'value' => 60, 'price' => 1000, 'sort_order' => 2],
            ['name' => '3 Hours', 'type' => 'time', 'value' => 180, 'price' => 2000, 'sort_order' => 3],
            ['name' => '24 Hours', 'type' => 'time', 'value' => 1440, 'price' => 3000, 'sort_order' => 4],
            ['name' => '7 Days', 'type' => 'time', 'value' => 10080, 'price' => 10000, 'sort_order' => 5],
            ['name' => '500 MB', 'type' => 'data', 'value' => 500, 'price' => 500, 'sort_order' => 6],
            ['name' => '1 GB', 'type' => 'data', 'value' => 1024, 'price' => 1000, 'sort_order' => 7],
            ['name' => '5 GB', 'type' => 'data', 'value' => 5120, 'price' => 5000, 'sort_order' => 8],
            ['name' => 'Unlimited 24H', 'type' => 'unlimited', 'value' => null, 'duration_minutes' => 1440, 'price' => 5000, 'sort_order' => 9],
        ])->each(fn (array $data) => Plan::create([
            'validity_days' => 1,
            'is_active' => true,
            ...$data,
        ]));

        // Create demo devices
        Device::factory(6)->create();

        // Create demo sessions linked to real plans
        $planIds = $plans->pluck('id');
        GuestSession::factory(25)->create([
            'plan_id' => fn () => $planIds->random(),
        ]);

        // Create demo payments linked to real plans
        Payment::factory(40)->completed()->create([
            'plan_id' => fn () => $planIds->random(),
        ]);
        Payment::factory(5)->create([
            'status' => 'pending',
            'plan_id' => fn () => $planIds->random(),
        ]);

        // Create payment gateway settings
        PaymentGatewaySetting::create([
            'gateway' => 'mpesa',
            'display_name' => 'M-Pesa',
            'is_active' => true,
            'config' => [
                'consumer_key' => '',
                'consumer_secret' => '',
                'shortcode' => '',
                'passkey' => '',
                'callback_url' => config('app.url') . '/api/mpesa/callback',
                'environment' => 'sandbox',
            ],
        ]);
        PaymentGatewaySetting::create([
            'gateway' => 'airtel',
            'display_name' => 'Airtel Money',
            'is_active' => false,
            'config' => [],
        ]);
        PaymentGatewaySetting::create([
            'gateway' => 'tigo',
            'display_name' => 'Tigo Pesa',
            'is_active' => false,
            'config' => [],
        ]);
    }
}
