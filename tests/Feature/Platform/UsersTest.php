<?php

use App\Livewire\Platform\Users;
use App\Models\User;
use Livewire\Livewire;

test('platform users page requires admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertRedirect(route('dashboard'));
});

test('platform users page loads for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Users');
});

test('admin can list all platform users', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->assertSee($admin->name)
        ->assertSee($customer->name)
        ->assertSee($customer->email);
});

test('admin can search users', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create(['name' => 'UniqueTestUser']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->set('search', 'UniqueTestUser')
        ->assertSee('UniqueTestUser');
});

test('admin can filter users by role', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'CustomerOnly']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->set('roleFilter', 'user')
        ->assertSee('CustomerOnly');
});

test('admin can create a new user', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('create')
        ->set('name', 'New Test User')
        ->set('email', 'newtest@example.com')
        ->set('password', 'password123')
        ->set('role', 'user')
        ->set('brandName', 'Test WiFi')
        ->call('save');

    $user = User::where('email', 'newtest@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('New Test User');
    expect($user->workspace)->not->toBeNull();
    expect($user->workspace->brand_name)->toBe('Test WiFi');
});

test('admin can edit an existing user', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('edit', $customer->id)
        ->set('name', 'Updated Name')
        ->call('save');

    $customer->refresh();
    expect($customer->name)->toBe('Updated Name');
});

test('admin can suspend a workspace', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('viewUser', $customer->id)
        ->call('suspendWorkspace', $customer->workspace->id);

    $customer->workspace->refresh();
    expect($customer->workspace->is_suspended)->toBeTrue();
});

test('admin can unsuspend a workspace', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $customer->workspace->update(['is_suspended' => true, 'suspended_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('unsuspendWorkspace', $customer->workspace->id);

    $customer->workspace->refresh();
    expect($customer->workspace->is_suspended)->toBeFalse();
});

test('admin can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $customerId = $customer->id;

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('deleteUser', $customerId);

    expect(User::find($customerId))->toBeNull();
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->call('deleteUser', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});

test('non-admin cannot access platform users component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Users::class)
        ->assertForbidden();
});
