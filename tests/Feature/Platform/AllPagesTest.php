<?php

use App\Livewire\Platform\AllDevices;
use App\Livewire\Platform\AllPayments;
use App\Livewire\Platform\AllSessions;
use App\Models\User;
use Livewire\Livewire;

// ── Route-level access tests ──

test('platform all-payments page requires admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/all-payments')
        ->assertRedirect(route('dashboard'));
});

test('platform all-sessions page requires admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/all-sessions')
        ->assertRedirect(route('dashboard'));
});

test('platform all-devices page requires admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/all-devices')
        ->assertRedirect(route('dashboard'));
});

test('platform all-payments page loads for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/all-payments')
        ->assertOk()
        ->assertSee('All Payments');
});

test('platform all-sessions page loads for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/all-sessions')
        ->assertOk()
        ->assertSee('All Sessions');
});

test('platform all-devices page loads for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/all-devices')
        ->assertOk()
        ->assertSee('All Devices');
});

// ── Livewire component access tests ──

test('non-admin cannot access all-payments component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AllPayments::class)
        ->assertForbidden();
});

test('non-admin cannot access all-sessions component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AllSessions::class)
        ->assertForbidden();
});

test('non-admin cannot access all-devices component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AllDevices::class)
        ->assertForbidden();
});

// ── Admin can load cross-workspace data ──

test('admin sees cross-workspace payments', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(AllPayments::class)
        ->assertOk();
});

test('admin sees cross-workspace sessions', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(AllSessions::class)
        ->assertOk();
});

test('admin sees cross-workspace devices', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(AllDevices::class)
        ->assertOk();
});
