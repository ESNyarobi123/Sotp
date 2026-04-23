<?php

use App\Livewire\Platform\Workspaces;
use App\Models\User;
use Livewire\Livewire;

test('platform workspaces page requires admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/workspaces')
        ->assertRedirect(route('dashboard'));
});

test('platform workspaces page loads for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/workspaces')
        ->assertOk()
        ->assertSee('Workspaces');
});

test('admin can list all workspaces', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->assertSee($admin->workspace->brand_name)
        ->assertSee($customer->workspace->brand_name);
});

test('admin can search workspaces by brand name', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $customer->workspace->update(['brand_name' => 'UniqueBrandSearch']);

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->set('search', 'UniqueBrandSearch')
        ->assertSee('UniqueBrandSearch');
});

test('admin can filter workspaces by status', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $customer->workspace->update(['is_suspended' => true, 'suspended_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->set('statusFilter', 'suspended')
        ->assertSee($customer->workspace->brand_name);
});

test('admin can edit workspace limits', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->call('editLimits', $customer->workspace->id)
        ->set('maxDevices', 25)
        ->set('maxPlans', 50)
        ->set('maxSessions', 500)
        ->call('saveLimits');

    $customer->workspace->refresh();
    expect($customer->workspace->max_devices)->toBe(25);
    expect($customer->workspace->max_plans)->toBe(50);
    expect($customer->workspace->max_sessions)->toBe(500);
});

test('admin can suspend a workspace', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->call('suspend', $customer->workspace->id);

    $customer->workspace->refresh();
    expect($customer->workspace->is_suspended)->toBeTrue();
    expect($customer->workspace->suspension_reason)->toBe('Suspended by admin');
});

test('admin can unsuspend a workspace', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $customer->workspace->update(['is_suspended' => true, 'suspended_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Workspaces::class)
        ->call('unsuspend', $customer->workspace->id);

    $customer->workspace->refresh();
    expect($customer->workspace->is_suspended)->toBeFalse();
    expect($customer->workspace->suspension_reason)->toBeNull();
});

test('non-admin cannot access platform workspaces component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Workspaces::class)
        ->assertForbidden();
});
