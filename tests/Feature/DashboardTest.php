<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin sees admin dashboard with charts and device stats', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Online Users')
        ->assertSee('Revenue Today')
        ->assertSee('Devices Online')
        ->assertSee('Sessions Today');
});

test('admin dashboard shows failed provisioning guidance when omada workspace setup fails', function () {
    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'omada_site_id' => null,
        'provisioning_status' => 'failed',
        'provisioning_error' => 'Controller temporarily unavailable',
        'provisioning_attempts' => 2,
        'provisioning_last_attempted_at' => now()->subMinutes(5),
        'provisioning_next_retry_at' => now()->addMinute(),
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Controller is temporarily unavailable')
        ->assertSee('Attempts: 2')
        ->assertSee('Controller temporarily unavailable')
        ->assertSee('Open Omada Integration');
});

test('customer sees customer dashboard with portal link and basic stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee($user->workspace->brand_name)
        ->assertSee('Online')
        ->assertSee('Sessions')
        ->assertSee('This month');
});

test('customer dashboard shows provisioning summary when omada site is not ready', function () {
    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => null,
        'provisioning_status' => 'pending',
        'provisioning_attempts' => 1,
        'provisioning_last_attempted_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pending')
        ->assertSee('Attempts: 1')
        ->assertSee('WiFi location is queued for setup');
});

test('customer dashboard does not show admin-only device management stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertDontSee('Devices Online')
        ->assertDontSee('Revenue — Last 7 Days');
});

test('dashboard still loads when workspace wallets table is unavailable', function () {
    $user = User::factory()->create();

    Schema::disableForeignKeyConstraints();
    Schema::drop('workspace_wallets');
    Schema::enableForeignKeyConstraints();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($user->workspace->brand_name)
        ->assertSee('This month');
});
