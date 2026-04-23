<?php

use App\Models\GuestSession;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

test('expires sessions whose time has elapsed', function () {
    $plan = Plan::factory()->create();

    // Active session that expired 5 minutes ago
    $expired = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'active',
        'time_expires' => now()->subMinutes(5),
    ]);

    // Active session still valid (no data limit to avoid data-based expiry)
    $valid = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'active',
        'time_expires' => now()->addHour(),
        'data_limit_mb' => null,
        'data_used_mb' => 0,
    ]);

    $this->artisan('sessions:expire')
        ->assertSuccessful();

    expect($expired->fresh()->status)->toBe('expired');
    expect($expired->fresh()->time_ended)->not->toBeNull();
    expect($valid->fresh()->status)->toBe('active');
});

test('expires sessions whose data limit is reached', function () {
    $plan = Plan::factory()->create(['type' => 'data', 'value' => 100]);

    $exhausted = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'active',
        'data_limit_mb' => 100,
        'data_used_mb' => 105,
        'time_expires' => now()->addHour(),
    ]);

    $underLimit = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'active',
        'data_limit_mb' => 100,
        'data_used_mb' => 50,
        'time_expires' => now()->addHour(),
    ]);

    $this->artisan('sessions:expire')
        ->assertSuccessful();

    expect($exhausted->fresh()->status)->toBe('expired');
    expect($underLimit->fresh()->status)->toBe('active');
});

test('does not touch already expired or disconnected sessions', function () {
    $plan = Plan::factory()->create();

    $alreadyExpired = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'expired',
        'time_expires' => now()->subHour(),
        'time_ended' => now()->subMinutes(30),
    ]);

    $disconnected = GuestSession::factory()->create([
        'plan_id' => $plan->id,
        'status' => 'disconnected',
        'time_expires' => now()->subHour(),
    ]);

    $this->artisan('sessions:expire')
        ->expectsOutput('No sessions to expire.')
        ->assertSuccessful();

    expect($alreadyExpired->fresh()->status)->toBe('expired');
    expect($disconnected->fresh()->status)->toBe('disconnected');
});
