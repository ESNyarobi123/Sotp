<?php

use App\Models\Device;
use App\Models\GuestSession;
use App\Models\OmadaSetting;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\User;

test('plan factory creates valid model', function () {
    $plan = Plan::factory()->create();

    expect($plan)->toBeInstanceOf(Plan::class)
        ->and($plan->name)->toBeString()
        ->and($plan->type)->toBeIn(['time', 'data', 'unlimited'])
        ->and($plan->price)->toBeGreaterThan(0);
});

test('plan has many guest sessions', function () {
    $plan = Plan::factory()->create();
    GuestSession::factory(3)->create(['plan_id' => $plan->id]);

    expect($plan->guestSessions)->toHaveCount(3);
});

test('plan has many payments', function () {
    $plan = Plan::factory()->create();
    Payment::factory(2)->create(['plan_id' => $plan->id]);

    expect($plan->payments)->toHaveCount(2);
});

test('plan active scope filters correctly', function () {
    Plan::factory()->create(['is_active' => true]);
    Plan::factory()->create(['is_active' => false]);

    expect(Plan::active()->count())->toBe(1);
});

test('plan formatted value returns human-readable string', function () {
    $timePlan = Plan::factory()->create(['type' => 'time', 'value' => 60]);
    $dataPlan = Plan::factory()->create(['type' => 'data', 'value' => 1024]);
    $unlimitedPlan = Plan::factory()->create(['type' => 'unlimited', 'value' => null, 'duration_minutes' => 1440]);

    expect($timePlan->formattedValue())->toBe('1 hours')
        ->and($dataPlan->formattedValue())->toBe('1 GB')
        ->and($unlimitedPlan->formattedValue())->toBe('24 hours unlimited');
});

test('device factory creates valid model', function () {
    $device = Device::factory()->create();

    expect($device)->toBeInstanceOf(Device::class)
        ->and($device->ap_mac)->toBeString()
        ->and($device->status)->toBeIn(['online', 'offline', 'unknown']);
});

test('guest session belongs to plan', function () {
    $session = GuestSession::factory()->create();

    expect($session->plan)->toBeInstanceOf(Plan::class);
});

test('guest session is active checks time and data', function () {
    $active = GuestSession::factory()->create([
        'status' => 'active',
        'time_expires' => now()->addHour(),
        'data_limit_mb' => 1024,
        'data_used_mb' => 100,
    ]);
    $expired = GuestSession::factory()->create([
        'status' => 'active',
        'time_expires' => now()->subHour(),
    ]);

    expect($active->isActive())->toBeTrue()
        ->and($expired->isActive())->toBeFalse();
});

test('payment belongs to plan and guest session', function () {
    $session = GuestSession::factory()->create();
    $payment = Payment::factory()->create([
        'plan_id' => $session->plan_id,
        'guest_session_id' => $session->id,
    ]);

    expect($payment->plan)->toBeInstanceOf(Plan::class)
        ->and($payment->guestSession)->toBeInstanceOf(GuestSession::class);
});

test('payment completed scope filters correctly', function () {
    Payment::factory()->create(['status' => 'completed']);
    Payment::factory()->create(['status' => 'pending']);
    Payment::factory()->create(['status' => 'failed']);

    expect(Payment::completed()->count())->toBe(1);
});

test('omada setting encrypts sensitive fields', function () {
    $setting = OmadaSetting::factory()->create([
        'username' => 'test_admin',
        'password' => 'secret123',
    ]);
    $fresh = OmadaSetting::find($setting->id);

    expect($fresh->username)->toBe('test_admin')
        ->and($fresh->password)->toBe('secret123');
});

test('payment gateway setting encrypts config', function () {
    $gw = PaymentGatewaySetting::factory()->create();
    $fresh = PaymentGatewaySetting::find($gw->id);

    expect($fresh->config)->toBeArray();
});

test('user has admin role check', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $guest = User::factory()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($guest->isAdmin())->toBeFalse();
});
