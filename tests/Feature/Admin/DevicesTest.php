<?php

use App\Livewire\Admin\Devices;
use App\Models\Device;
use App\Models\User;
use Livewire\Livewire;

test('devices page shows device data', function () {
    $user = User::factory()->create();
    $device = Device::factory()->online()->create(['name' => 'Lobby AP Test']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->assertSee('Lobby AP Test')
        ->assertSee($device->ap_mac);
});

test('devices page can filter by status', function () {
    $user = User::factory()->create();
    $online = Device::factory()->online()->create(['name' => 'Online Device']);
    $offline = Device::factory()->offline()->create(['name' => 'Offline Device']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('statusFilter', 'online')
        ->assertSee('Online Device')
        ->assertDontSee('Offline Device');
});

test('devices page can search by name', function () {
    $user = User::factory()->create();
    Device::factory()->online()->create(['name' => 'Lobby AP']);
    Device::factory()->online()->create(['name' => 'Rooftop AP']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('search', 'Lobby')
        ->assertSee('Lobby AP')
        ->assertDontSee('Rooftop AP');
});

test('can create a new device', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('create')
        ->set('name', 'New Test AP')
        ->set('ap_mac', 'AA:BB:CC:DD:EE:FF')
        ->set('ip_address', '192.168.1.100')
        ->set('model', 'EAP620 HD')
        ->set('site_name', 'Main Branch')
        ->call('save');

    $this->assertDatabaseHas('devices', [
        'name' => 'New Test AP',
        'ap_mac' => 'AA:BB:CC:DD:EE:FF',
        'model' => 'EAP620 HD',
        'status' => 'unknown',
    ]);
});

test('can edit an existing device', function () {
    $user = User::factory()->create();
    $device = Device::factory()->online()->create(['name' => 'Old Name']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('edit', $device->id)
        ->set('name', 'Updated Name')
        ->call('save');

    expect($device->fresh()->name)->toBe('Updated Name');
});

test('can delete a device', function () {
    $user = User::factory()->create();
    $device = Device::factory()->create();

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('delete', $device->id);

    $this->assertDatabaseMissing('devices', ['id' => $device->id]);
});

test('create form validates MAC address format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('create')
        ->set('name', 'Test AP')
        ->set('ap_mac', 'invalid-mac')
        ->call('save')
        ->assertHasErrors(['ap_mac']);
});

test('devices page shows correct status counts', function () {
    $user = User::factory()->create();
    Device::factory()->online()->count(3)->create();
    Device::factory()->offline()->count(2)->create();

    $component = Livewire::actingAs($user)->test(Devices::class);

    expect($component->get('onlineCount'))->toBe(3);
    expect($component->get('offlineCount'))->toBe(2);
    expect($component->get('totalCount'))->toBe(5);
});
