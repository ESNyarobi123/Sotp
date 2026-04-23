<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
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

        // Create admin user (avoid User factory hooks so we attach a single workspace below)
        $admin = new User;
        $admin->forceFill([
            'name' => 'Admin User',
            'email' => 'admin@skyomada.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->save();
        $admin->assignRole($adminRole);

        $workspace = Workspace::create([
            'user_id' => $admin->id,
            'brand_name' => 'SKY Omada Demo',
            'public_slug' => Workspace::uniquePublicSlugFromBrand('SKY Omada Demo'),
            'omada_site_id' => env('OMADA_SITE_ID') ?: null,
            'provisioning_status' => env('OMADA_SITE_ID') ? 'ready' : 'pending',
            'provisioning_error' => null,
        ]);

        // Create predefined plans
        collect([
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
            'workspace_id' => $workspace->id,
            'validity_days' => 1,
            'is_active' => true,
            ...$data,
        ]));

        // Create demo devices
        Device::factory(6)->create(['workspace_id' => $workspace->id]);

        // Create demo sessions linked to real plans
        $planIds = Plan::query()->where('workspace_id', $workspace->id)->pluck('id');
        GuestSession::factory(25)->create([
            'workspace_id' => $workspace->id,
            'plan_id' => fn () => $planIds->random(),
        ]);

        // Create demo payments linked to real plans
        Payment::factory(40)->completed()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => fn () => $planIds->random(),
        ]);
        Payment::factory(5)->create([
            'workspace_id' => $workspace->id,
            'status' => 'pending',
            'plan_id' => fn () => $planIds->random(),
        ]);

        // Create payment gateway settings
        PaymentGatewaySetting::create([
            'workspace_id' => $workspace->id,
            'gateway' => 'mpesa',
            'display_name' => 'M-Pesa',
            'is_active' => true,
            'config' => [
                'consumer_key' => '',
                'consumer_secret' => '',
                'shortcode' => '',
                'passkey' => '',
                'callback_url' => config('app.url').'/api/mpesa/callback',
                'environment' => 'sandbox',
            ],
        ]);
        PaymentGatewaySetting::create([
            'workspace_id' => $workspace->id,
            'gateway' => 'airtel',
            'display_name' => 'Airtel Money',
            'is_active' => false,
            'config' => [],
        ]);
        PaymentGatewaySetting::create([
            'workspace_id' => $workspace->id,
            'gateway' => 'tigo',
            'display_name' => 'Tigo Pesa',
            'is_active' => false,
            'config' => [],
        ]);
    }
}
