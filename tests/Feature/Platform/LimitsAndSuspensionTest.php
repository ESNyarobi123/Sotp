<?php

use App\Livewire\Admin\Devices;
use App\Livewire\Admin\Plans;
use App\Models\Device;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('device limit prevents creating more devices than max', function () {
    $user = User::factory()->create();
    $user->workspace->update(['max_devices' => 2]);

    Device::factory()->count(2)->create(['workspace_id' => $user->workspace->id]);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('create')
        ->set('name', 'Overflow AP')
        ->set('ap_mac', 'AA:BB:CC:DD:EE:FF')
        ->call('save');

    expect(Device::where('workspace_id', $user->workspace->id)->count())->toBe(2);
});

test('plan limit prevents creating more plans than max', function () {
    $user = User::factory()->create();
    $user->workspace->update(['max_plans' => 1]);

    Plan::factory()->create(['workspace_id' => $user->workspace->id]);

    Livewire::actingAs($user)
        ->test(Plans::class)
        ->call('create')
        ->set('name', 'Overflow Plan')
        ->set('type', 'time')
        ->set('value', 60)
        ->set('price', '1000')
        ->set('validity_days', 1)
        ->set('is_active', true)
        ->call('save');

    expect(Plan::where('workspace_id', $user->workspace->id)->count())->toBe(1);
});

test('suspended workspace blocks non-admin users', function () {
    $user = User::factory()->create();
    $user->workspace->update(['is_suspended' => true, 'suspended_at' => now()]);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->assertForbidden();
});

test('suspended workspace does not block admin users', function () {
    $admin = User::factory()->admin()->create();
    $admin->workspace->update(['is_suspended' => true, 'suspended_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Devices::class)
        ->assertOk();
});
