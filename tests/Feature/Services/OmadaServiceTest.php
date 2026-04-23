<?php

use App\Models\Device;
use App\Models\OmadaSetting;
use App\Models\Workspace;
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

test('auditCapabilities reports implemented and adopt needs-config statuses from configuration', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.url' => 'https://omada.test',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => 'site456',
    ]);

    $service = new OmadaService;
    $capabilities = collect($service->auditCapabilities())->keyBy('title');

    expect($capabilities['Workspace site provisioning']['status'])->toBe('implemented');
    expect($capabilities['Device sync, rename, and reboot']['status'])->toBe('implemented');
    expect($capabilities['External portal authorize / unauthorize']['status'])->toBe('implemented');
    expect($capabilities['Pending device adopt / assign to site']['status'])->toBe('needs_config');
});

test('createSiteForBrand recovers duplicate site by looking up existing site id', function () {
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
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites' => Http::response([
            'msg' => 'Site already exists',
        ], 409),
        'omada.test/openapi/v1/ctrl123/sites?page=1&pageSize=100' => Http::response([
            'errorCode' => 0,
            'result' => [
                'data' => [
                    ['siteId' => 'existing-site-123', 'name' => 'Acme Lounge'],
                ],
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->createSiteForBrand('Acme Lounge');

    expect($result['success'])->toBeTrue();
    expect($result['siteId'])->toBe('existing-site-123');
    expect($result['retryable'])->toBeFalse();
});

test('createSiteForBrand classifies controller unavailable failures as retryable', function () {
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
            'result' => ['accessToken' => 'abc-token', 'csrfToken' => 'csrf-123'],
        ]),
        'omada.test/openapi/v1/ctrl123/sites' => Http::response([
            'msg' => 'Controller temporarily unavailable',
        ], 503),
    ]);

    Cache::forget('omada_openapi_token');

    $service = new OmadaService;
    $result = $service->createSiteForBrand('Retry Branch');

    expect($result['success'])->toBeFalse();
    expect($result['retryable'])->toBeTrue();
    expect($result['error_code'])->toBe('controller_unavailable');
});

test('automationReadiness reports missing and ready items correctly', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => null,
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.site_id' => null,
    ]);

    $service = new OmadaService;
    $readiness = collect($service->automationReadiness('https://portal.test.com'))->keyBy('label');

    expect($readiness['Open API client ID available']['ready'])->toBeTrue();
    expect($readiness['Open API client secret available']['ready'])->toBeFalse();
    expect($readiness['Controller ID available']['ready'])->toBeTrue();
    expect($readiness['Default site ID available']['ready'])->toBeFalse();
    expect($readiness['External portal URL available']['ready'])->toBeTrue();
});

test('finalizeSiteReadiness reports workspace last-mile readiness correctly', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
    ]);

    $workspace = Workspace::factory()->omadaSite('site-456')->create();

    OmadaSetting::factory()->create([
        'controller_url' => 'https://omada.test',
        'username' => 'admin',
        'hotspot_operator_name' => 'operator',
        'hotspot_operator_password' => 'secret',
        'is_connected' => true,
        'omada_id' => 'ctrl123',
    ]);

    $service = new OmadaService;
    $readiness = collect($service->finalizeSiteReadiness($workspace, 'https://portal.test.com'))->keyBy('label');

    expect($readiness['Controller connection verified']['ready'])->toBeTrue();
    expect($readiness['Open API automation configured']['ready'])->toBeTrue();
    expect($readiness['Workspace Omada site assigned']['ready'])->toBeTrue();
    expect($readiness['Hotspot operator credentials saved']['ready'])->toBeTrue();
});

test('deviceAdoptionStatus reports blocked when workspace readiness is incomplete', function () {
    config([
        'services.omada.client_id' => null,
        'services.omada.client_secret' => null,
        'services.omada.controller_id' => null,
        'services.omada.url' => null,
    ]);

    $workspace = Workspace::factory()->pending()->create();

    $service = new OmadaService;
    $status = $service->deviceAdoptionStatus($workspace);

    expect($status['status'])->toBe('blocked');
    expect($status['blockers'])->not->toBeEmpty();
});

test('deviceAdoptionStatus reports manual flow when workspace readiness is complete', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
    ]);

    $workspace = Workspace::factory()->omadaSite('site-456')->create();

    Device::factory()->for($workspace)->create([
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

    $service = new OmadaService;
    $status = $service->deviceAdoptionStatus($workspace);

    expect($status['status'])->toBe('manual');
    expect($status['blockers'])->toBeEmpty();
    expect($status['endpoint_verified'])->toBeTrue();
    expect($status['title'])->toContain('verified');
});

test('pendingDeviceInventory returns isolated and preconfigured devices for a ready workspace', function () {
    config([
        'services.omada.client_id' => 'test-client-id',
        'services.omada.client_secret' => 'test-secret',
        'services.omada.controller_id' => 'ctrl123',
        'services.omada.url' => 'https://omada.test',
        'services.omada.verify_ssl' => false,
    ]);

    $workspace = Workspace::factory()->omadaSite('site-456')->create();

    Device::factory()->for($workspace)->create([
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
                'total' => 2,
                'isolated' => [
                    ['name' => 'Outdoor AP', 'mac' => 'aa-bb-cc-dd-ee-01', 'model' => 'EAP610', 'type' => 'ap'],
                ],
                'preconfig' => [
                    ['name' => 'Front Desk AP', 'mac' => 'aa-bb-cc-dd-ee-02', 'model' => 'EAP650', 'type' => 'ap'],
                ],
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');
    Cache::forget('omada_pending_device_inventory:'.$workspace->id.':'.$workspace->omada_site_id);

    $service = new OmadaService;
    $inventory = $service->pendingDeviceInventory($workspace);

    expect($inventory['status'])->toBe('ready');
    expect($inventory['total'])->toBe(2);
    expect($inventory['isolated'][0]['mac'])->toBe('AA:BB:CC:DD:EE:01');
    expect($inventory['isolated'][0]['in_sky'])->toBeTrue();
    expect($inventory['isolated'][0]['local_device_name'])->toBe('Outdoor AP Local');
    expect($inventory['preconfig'][0]['name'])->toBe('Front Desk AP');
    expect($inventory['correlation']['already_in_sky'])->toBe(1);
    expect($inventory['correlation']['not_in_sky'])->toBe(1);
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

    $workspace = Workspace::factory()->omadaSite('site456')->create();

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
    $result = $service->syncDevicesFromOmada($workspace);

    expect($result['success'])->toBeTrue();
    expect($result['synced'])->toBe(2);
    expect(Device::where('workspace_id', $workspace->id)->count())->toBe(2);

    $lobby = Device::where('workspace_id', $workspace->id)->where('ap_mac', 'AA:BB:CC:DD:EE:01')->first();
    expect($lobby->name)->toBe('Lobby AP');
    expect($lobby->status)->toBe('online');
    expect($lobby->omada_device_id)->toBe('dev-001');
    expect($lobby->firmware_version)->toBe('1.2.3');
    expect($lobby->clients_count)->toBe(12);
    expect($lobby->uptime_seconds)->toBe(86400);

    $office = Device::where('workspace_id', $workspace->id)->where('ap_mac', 'AA:BB:CC:DD:EE:02')->first();
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

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->renameDevice('AA:BB:CC:DD:EE:01', 'New Lobby AP', $workspace);

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

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->rebootDevice('AA:BB:CC:DD:EE:01', $workspace);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA:BB:CC:DD:EE:01/reboot')
            && $request->method() === 'POST';
    });
});

test('startAdoptDevice sends POST request to Omada with device credentials', function () {
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
        'omada.test/openapi/v1/ctrl123/sites/site456/devices/AA-BB-CC-DD-EE-01/start-adopt' => Http::response([
            'errorCode' => 0,
            'msg' => 'Success.',
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->startAdoptDevice('AA:BB:CC:DD:EE:01', 'ubnt', 'secret123', $workspace);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'devices/AA-BB-CC-DD-EE-01/start-adopt')
            && $request->method() === 'POST'
            && $request['username'] === 'ubnt'
            && $request['password'] === 'secret123';
    });
});

test('getAdoptDeviceResult returns success for adopted device', function () {
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
        'omada.test/openapi/v1/ctrl123/sites/site456/devices/AA-BB-CC-DD-EE-01/adopt-result' => Http::response([
            'errorCode' => 0,
            'result' => [
                'deviceMac' => 'AA-BB-CC-DD-EE-01',
                'adoptErrorCode' => 0,
                'adoptFailedType' => -1,
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->getAdoptDeviceResult('AA:BB:CC:DD:EE:01', $workspace);

    expect($result['success'])->toBeTrue();
    expect($result['adopted'])->toBeTrue();
    expect($result['device_mac'])->toBe('AA:BB:CC:DD:EE:01');
    expect($result['error'])->toBeNull();
});

test('getAdoptDeviceResult returns credential error when Omada rejects adoption', function () {
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
        'omada.test/openapi/v1/ctrl123/sites/site456/devices/AA-BB-CC-DD-EE-01/adopt-result' => Http::response([
            'errorCode' => 0,
            'result' => [
                'deviceMac' => 'AA-BB-CC-DD-EE-01',
                'adoptErrorCode' => -39003,
                'adoptFailedType' => -2,
            ],
        ]),
    ]);

    Cache::forget('omada_openapi_token');

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->getAdoptDeviceResult('AA:BB:CC:DD:EE:01', $workspace);

    expect($result['success'])->toBeFalse();
    expect($result['adopted'])->toBeFalse();
    expect($result['adopt_error_code'])->toBe(-39003);
    expect($result['adopt_failed_type'])->toBe(-2);
    expect($result['error'])->toContain('username or password is incorrect');
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

    $workspace = new Workspace(['omada_site_id' => 'site456']);

    $service = new OmadaService;
    $result = $service->authorizeClient([
        'clientMac' => 'AA:BB:CC:DD:EE:FF',
        'apMac' => '11:22:33:44:55:66',
        'ssid' => 'SKY-WiFi',
        'minutes' => 60,
    ], $workspace);

    expect($result['success'])->toBeTrue();
    expect($result['authId'])->toBe('auth-id-999');
});
