<?php

use App\Livewire\Admin\Devices;
use App\Models\Device;
use App\Models\OmadaSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('devices page shows device data', function () {
    $user = User::factory()->create();
    $device = Device::factory()->online()->for($user->workspace)->create(['name' => 'Lobby AP Test']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->assertSee('Lobby AP Test')
        ->assertSee($device->ap_mac);
});

test('devices page shows provisioning guidance when workspace omada setup has failed', function () {
    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => null,
        'provisioning_status' => 'failed',
        'provisioning_error' => 'Controller temporarily unavailable',
        'provisioning_attempts' => 2,
        'provisioning_last_attempted_at' => now()->subMinutes(5),
        'provisioning_next_retry_at' => now()->addMinute(),
    ]);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->assertSee('Controller is temporarily unavailable')
        ->assertSee('Attempts: 2')
        ->assertSee('Controller temporarily unavailable');
});

test('devices setup guide shows blocked adoption guidance from backend status', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('showGuide', true)
        ->assertSee('Finish workspace readiness before Step 3 device adoption')
        ->assertSee('This workspace does not have an Omada site assigned yet.');
});

test('devices setup guide shows discovered pending devices when inventory endpoint returns data', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
        'services.omada.verify_ssl' => false,
    ]);

    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    Device::factory()->for($user->workspace)->create([
        'name' => 'Outdoor AP Local',
        'ap_mac' => 'AA:BB:CC:DD:EE:01',
        'status' => 'offline',
    ]);

    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test',
        'username' => 'admin',
        'is_connected' => true,
        'omada_id' => 'ctrl123',
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v2/ctrl123/sites/site-456/topology/isolated-and-pre-config' => Http::response([
            'errorCode' => 0,
            'result' => [
                'total' => 1,
                'isolated' => [
                    ['name' => 'Outdoor AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
                ],
                'preconfig' => [],
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');
    Cache::forget('omada_pending_device_inventory:'.$user->workspace->id.':site-456');

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('showGuide', true)
        ->assertSee('Discovered pending devices:')
        ->assertSee('Already in SKY:')
        ->assertSee('Not yet in SKY:')
        ->assertSee('Outdoor AP')
        ->assertSee('AA:BB:CC:DD:EE:01')
        ->assertSee('In SKY')
        ->assertSee('Local device: Outdoor AP Local (offline)');
});

test('devices setup guide lets admin start adopt request for selected pending device', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
        'services.omada.verify_ssl' => false,
    ]);

    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test',
        'username' => 'admin',
        'is_connected' => true,
        'omada_id' => 'ctrl123',
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v2/ctrl123/sites/site-456/topology/isolated-and-pre-config' => Http::response([
            'errorCode' => 0,
            'result' => [
                'total' => 1,
                'isolated' => [
                    ['name' => 'Outdoor AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
                ],
                'preconfig' => [],
            ],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site-456/devices/AA-BB-CC-DD-EE-01/start-adopt' => Http::response([
            'errorCode' => 0,
            'msg' => 'Success.',
        ]),
    ]);

    Cache::forget('omada_openapi_token');
    Cache::forget('omada_pending_device_inventory:'.$user->workspace->id.':site-456');

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('showGuide', true)
        ->assertSee('Admin adopt trigger')
        ->call('selectPendingDeviceForAdoption', 'AA:BB:CC:DD:EE:01')
        ->assertSet('adoptDeviceMac', 'AA:BB:CC:DD:EE:01')
        ->set('adoptDeviceUsername', 'admin')
        ->set('adoptDevicePassword', 'secret123')
        ->call('startDeviceAdoption')
        ->assertSet('adoptDeviceResult.status', 'pending')
        ->assertSet('adoptDeviceResult.title', 'Adopt request sent');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA-BB-CC-DD-EE-01/start-adopt')
            && $request->method() === 'POST'
            && $request['username'] === 'admin'
            && $request['password'] === 'secret123';
    });
});

test('devices setup guide lets admin check adopt result for selected pending device', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
        'services.omada.verify_ssl' => false,
    ]);

    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test',
        'username' => 'admin',
        'is_connected' => true,
        'omada_id' => 'ctrl123',
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v2/ctrl123/sites/site-456/topology/isolated-and-pre-config' => Http::response([
            'errorCode' => 0,
            'result' => [
                'total' => 1,
                'isolated' => [
                    ['name' => 'Outdoor AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
                ],
                'preconfig' => [],
            ],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site-456/devices/AA-BB-CC-DD-EE-01/adopt-result' => Http::response([
            'errorCode' => 0,
            'result' => [
                'deviceMac' => 'AA-BB-CC-DD-EE-01',
                'adoptErrorCode' => 0,
                'adoptFailedType' => -1,
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');
    Cache::forget('omada_pending_device_inventory:'.$user->workspace->id.':site-456');

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('showGuide', true)
        ->set('adoptDeviceMac', 'AA:BB:CC:DD:EE:01')
        ->call('checkAdoptDeviceResult')
        ->assertSet('adoptDeviceResult.status', 'success')
        ->assertSet('adoptDeviceResult.title', 'Device adopted successfully')
        ->assertSee('Device adopted successfully');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA-BB-CC-DD-EE-01/adopt-result')
            && $request->method() === 'GET';
    });
});

test('devices setup guide does not allow non admin users to trigger adopt actions', function () {
    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    $component = Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('selectPendingDeviceForAdoption', 'AA:BB:CC:DD:EE:01');

    expect($component->get('adoptDeviceMac'))->not->toBe('AA:BB:CC:DD:EE:01');
});

test('devices setup guide refresh action clears pending inventory cache', function () {
    $user = User::factory()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    Cache::put('omada_pending_device_inventory:'.$user->workspace->id.':site-456', ['status' => 'ready'], now()->addMinute());

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('refreshPendingDeviceInventory');

    expect(Cache::has('omada_pending_device_inventory:'.$user->workspace->id.':site-456'))->toBeFalse();
});

test('devices page can filter by status', function () {
    $user = User::factory()->create();
    $online = Device::factory()->online()->for($user->workspace)->create(['name' => 'Online Device']);
    $offline = Device::factory()->offline()->for($user->workspace)->create(['name' => 'Offline Device']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->set('statusFilter', 'online')
        ->assertSee('Online Device')
        ->assertDontSee('Offline Device');
});

test('devices page can search by name', function () {
    $user = User::factory()->create();
    Device::factory()->online()->for($user->workspace)->create(['name' => 'Lobby AP']);
    Device::factory()->online()->for($user->workspace)->create(['name' => 'Rooftop AP']);

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
        'workspace_id' => $user->workspace->id,
        'name' => 'New Test AP',
        'ap_mac' => 'AA:BB:CC:DD:EE:FF',
        'model' => 'EAP620 HD',
        'status' => 'unknown',
    ]);
});

test('can edit an existing device', function () {
    $user = User::factory()->create();
    $device = Device::factory()->online()->for($user->workspace)->create(['name' => 'Old Name']);

    Livewire::actingAs($user)
        ->test(Devices::class)
        ->call('edit', $device->id)
        ->set('name', 'Updated Name')
        ->call('save');

    expect($device->fresh()->name)->toBe('Updated Name');
});

test('can delete a device', function () {
    $user = User::factory()->create();
    $device = Device::factory()->for($user->workspace)->create();

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
    Device::factory()->online()->for($user->workspace)->count(3)->create();
    Device::factory()->offline()->for($user->workspace)->count(2)->create();

    $component = Livewire::actingAs($user)->test(Devices::class);

    expect($component->get('onlineCount'))->toBe(3);
    expect($component->get('offlineCount'))->toBe(2);
    expect($component->get('totalCount'))->toBe(5);
});
