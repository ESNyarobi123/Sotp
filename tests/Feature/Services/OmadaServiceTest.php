<?php

use App\Models\Device;
use App\Services\OmadaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('isConfigured returns true when Open API credentials set', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
    ]);

    $service = new OmadaService;
    expect($service->isConfigured())->toBeTrue();
});

test('isConfigured returns false with no credentials', function () {
    config([
        'services.omada.client_id' => null,
        'services.omada.client_secret' => null,
    ]);

    $service = new OmadaService;
    expect($service->isConfigured())->toBeFalse();
});

test('authenticate caches token via Open API', function () {
    config([
        'services.omada.client_id' => 'test-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.verify_ssl' => false,
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => [
                'accessToken' => 'abc-token',
                'csrfToken' => 'csrf-123',
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->authenticate();

    expect($result)->toBeTrue();
    expect(Cache::get('omada_openapi_token'))->not->toBeNull();
    expect(Cache::get('omada_openapi_token')['accessToken'])->toBe('abc-token');
});

test('syncDevicesFromOmada creates devices with rich details', function () {
    config([
        'services.omada.client_id' => 'test-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => 'site456',
        'services.omada.verify_ssl' => false,
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites?*' => Http::response([
            'errorCode' => 0,
            'result' => [
                'data' => [
                    ['siteId' => 'site456', 'name' => 'Main Branch'],
                ],
            ],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site456/devices*' => Http::response([
            'errorCode' => 0,
            'result' => [
                'data' => [
                    [
                        'mac' => 'AA:BB:CC:DD:EE:01', 'name' => 'Lobby AP', 'model' => 'EAP610',
                        'ip' => '10.0.0.1', 'status' => 2, 'deviceId' => 'dev-001',
                        'firmwareVersion' => '1.2.3', 'clientNum' => 12, 'uptimeLong' => 86400,
                    ],
                    [
                        'mac' => 'AA:BB:CC:DD:EE:02', 'name' => 'Office AP', 'model' => 'EAP650',
                        'ip' => '10.0.0.2', 'status' => 0, 'deviceId' => 'dev-002',
                        'firmwareVersion' => '2.0.0', 'clientNum' => 0, 'uptimeLong' => 0,
                    ],
                ],
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->syncDevicesFromOmada();

    expect($result['success'])->toBeTrue();
    expect($result['synced'])->toBe(2);
    expect(Device::count())->toBe(2);

    $lobby = Device::where('ap_mac', 'AA:BB:CC:DD:EE:01')->first();
    expect($lobby->name)->toBe('Lobby AP');
    expect($lobby->status)->toBe('online');
    expect($lobby->omada_device_id)->toBe('dev-001');
    expect($lobby->firmware_version)->toBe('1.2.3');
    expect($lobby->clients_count)->toBe(12);
    expect($lobby->uptime_seconds)->toBe(86400);

    $office = Device::where('ap_mac', 'AA:BB:CC:DD:EE:02')->first();
    expect($office->status)->toBe('offline');
    expect($office->omada_device_id)->toBe('dev-002');
});

test('renameDevice sends PATCH request to Omada', function () {
    config([
        'services.omada.client_id' => 'test-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => 'site456',
        'services.omada.verify_ssl' => false,
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site456/devices/AA:BB:CC:DD:EE:01' => Http::response([
            'errorCode' => 0,
            'msg' => 'Success.',
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->renameDevice('AA:BB:CC:DD:EE:01', 'New Lobby AP');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA:BB:CC:DD:EE:01')
            && $request->method() === 'PATCH'
            && $request['name'] === 'New Lobby AP';
    });
});

test('rebootDevice sends POST request to Omada', function () {
    config([
        'services.omada.client_id' => 'test-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => 'site456',
        'services.omada.verify_ssl' => false,
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site456/devices/AA:BB:CC:DD:EE:01/reboot' => Http::response([
            'errorCode' => 0,
            'msg' => 'Success.',
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->rebootDevice('AA:BB:CC:DD:EE:01');

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA:BB:CC:DD:EE:01/reboot')
            && $request->method() === 'POST';
    });
});

test('authorizeClient sends correct Open API request', function () {
    config([
        'services.omada.client_id' => 'test-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => 'site456',
        'services.omada.verify_ssl' => false,
    ]);

    Http::fake([
        'omada.test/openapi/authorize/token*' => Http::response([
            'errorCode' => 0,
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites/site456/hotspot/extPortal/auth' => Http::response([
            'errorCode' => 0,
            'result' => ['clientId' => 'auth-id-999'],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->authorizeClient([
        'clientMac' => 'AA:BB:CC:DD:EE:FF',
        'apMac' => '11:22:33:44:55:66',
        'ssid' => 'SKY-WiFi',
        'minutes' => 60,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['authId'])->toBe('auth-id-999');
});
