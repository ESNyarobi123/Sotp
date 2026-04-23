<?php

use App\Livewire\Admin\OmadaIntegration;
use App\Models\Device;
use App\Models\OmadaSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('omada page renders settings form', function () {
    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'provisioning_attempts' => 2,
        'provisioning_last_attempted_at' => now()->subMinutes(5),
        'provisioning_next_retry_at' => now()->addMinute(),
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->assertSee('Omada Integration')
        ->assertSee('Controller Connection')
        ->assertSee('Hotspot')
        ->assertSee('Setup Guide')
        ->assertSee('Step 1 API Audit')
        ->assertSee('Open API Automation')
        ->assertSee('Automation Readiness')
        ->assertSee('Open API client ID available')
        ->assertSee('Workspace Provisioning')
        ->assertSee('Attempts')
        ->assertSee('Last attempt')
        ->assertSee('Next retry')
        ->assertSee('Step 3 Device Adoption')
        ->assertSee('Finalize Site Readiness')
        ->assertSee($user->workspace->brand_name);
});

test('omada page shows discovered pending devices when step 3 inventory is available', function () {
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

    Device::factory()->for($user->workspace)->create([
        'name' => 'Front Desk AP Local',
        'ap_mac' => 'AA:BB:CC:DD:EE:02',
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
                'isolated' => [],
                'preconfig' => [
                    ['name' => 'Front Desk AP', 'mac' => 'aa-bb-cc-dd-ee-02', 'model' => 'EAP650', 'type' => 'ap'],
                ],
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');
    Cache::forget('omada_pending_device_inventory:'.$user->workspace->id.':site-456');

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->assertSee('Discovered pending devices')
        ->assertSee('Admin adopt trigger')
        ->assertSee('Already in SKY')
        ->assertSee('Not yet in SKY')
        ->assertSee('Front Desk AP')
        ->assertSee('AA:BB:CC:DD:EE:02')
        ->assertSee('In SKY')
        ->assertSee('Local device: Front Desk AP Local (offline)');
});

test('omada page can select a pending device and start adopt request', function () {
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
                    ['name' => 'Roof AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
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
        ->test(OmadaIntegration::class)
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

test('omada page can check adopt result for selected device', function () {
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
                    ['name' => 'Roof AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
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
        ->test(OmadaIntegration::class)
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

test('omada page refresh action clears pending inventory cache', function () {
    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'omada_site_id' => 'site-456',
        'provisioning_status' => 'ready',
    ]);

    Cache::put('omada_pending_device_inventory:'.$user->workspace->id.':site-456', ['status' => 'ready'], now()->addMinute());

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('refreshPendingDeviceInventory');

    expect(Cache::has('omada_pending_device_inventory:'.$user->workspace->id.':site-456'))->toBeFalse();
});

test('retry provisioning does not queue when open api automation is not configured', function () {
    Queue::fake();

    config([
        'services.omada.client_id' => null,
        'services.omada.client_secret' => null,
        'services.omada.controller_id' => null,
        'services.omada.url' => null,
    ]);

    $user = User::factory()->admin()->create();
    $user->workspace->update([
        'provisioning_status' => 'failed',
        'provisioning_error' => 'Previous failure',
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('retryProvisioning');

    Queue::assertNothingPushed();

    expect($user->workspace->fresh()->provisioning_status)->toBe('failed');
});

test('can save omada settings', function () {
    $user = User::factory()->admin()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', 'https://omada.test.com')
        ->set('username', 'admin')
        ->set('password', 'secret123')
        ->set('hotspot_operator_name', 'operator')
        ->set('external_portal_url', 'https://portal.test.com')
        ->call('save');

    $settings = OmadaSetting::first();
    expect($settings->controller_url)->toBe('https://omada.test.com');
    expect($settings->username)->toBe('admin');
    expect($settings->hotspot_operator_name)->toBe('operator');
    expect($settings->external_portal_url)->toBe('https://portal.test.com');
});

test('save validates required fields', function () {
    $user = User::factory()->admin()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', '')
        ->set('username', '')
        ->call('save')
        ->assertHasErrors(['controller_url', 'username']);
});

test('save validates url format', function () {
    $user = User::factory()->admin()->create();

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->set('controller_url', 'not-a-url')
        ->set('username', 'admin')
        ->call('save')
        ->assertHasErrors(['controller_url']);
});

test('password fields are not pre-filled with existing values', function () {
    $user = User::factory()->admin()->create();
    OmadaSetting::factory()->create();

    $component = Livewire::actingAs($user)->test(OmadaIntegration::class);

    expect($component->get('password'))->toBe('');
    expect($component->get('api_key'))->toBe('');
    expect($component->get('hotspot_operator_password'))->toBe('');
});

test('existing settings are loaded on mount', function () {
    $user = User::factory()->admin()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://my-omada.com',
        'username' => 'myadmin',
        'site_id' => 'site-abc',
    ]);

    $component = Livewire::actingAs($user)->test(OmadaIntegration::class);

    expect($component->get('controller_url'))->toBe('https://my-omada.com');
    expect($component->get('username'))->toBe('myadmin');
    expect($component->get('site_id'))->toBe('site-abc');
});

test('test connection succeeds with valid response', function () {
    $user = User::factory()->admin()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test.com',
        'username' => 'admin',
        'password' => 'pass',
    ]);

    Http::fake([
        '*/api/info' => Http::response([
            'errorCode' => 0,
            'result' => ['omadacId' => 'abc123'],
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('testConnection');

    $settings = OmadaSetting::first();
    expect($settings->is_connected)->toBeTrue();
    expect($settings->omada_id)->toBe('abc123');
    expect($settings->last_synced_at)->not->toBeNull();
});

test('test connection fails gracefully', function () {
    $user = User::factory()->admin()->create();
    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test.com',
        'username' => 'admin',
        'password' => 'pass',
    ]);

    Http::fake([
        '*/api/info' => Http::response('Error', 500),
    ]);

    Livewire::actingAs($user)
        ->test(OmadaIntegration::class)
        ->call('testConnection');

    expect(OmadaSetting::first()->is_connected)->toBeFalse();
});
