<?php

namespace App\Services;

use App\Models\Device;
use App\Models\OmadaSetting;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Omada Controller integration — Open API only.
 *
 * Uses Client Credentials (client_id + client_secret) from config/services.php.
 * Falls back to DB-stored OmadaSetting when .env values are not present.
 * All tokens are cached in Redis for 50 minutes.
 */
class OmadaService
{
    private const TOKEN_CACHE_KEY = 'omada_openapi_token';

    private const TOKEN_TTL_MINUTES = 50;

    private ?string $accessToken = null;

    private ?string $csrfToken = null;

    // ─── Configuration Helpers ──────────────────────────────────

    /**
     * DB-stored settings (singleton row).
     */
    private function settings(): ?OmadaSetting
    {
        $settings = OmadaSetting::instance();

        return $settings->exists ? $settings : null;
    }

    /**
     * Resolve a value: .env config first, then DB fallback.
     */
    private function resolve(string $configKey, ?string $dbField = null): string
    {
        $envValue = config("services.omada.{$configKey}", '');

        if ($envValue !== '' && $envValue !== null) {
            return (string) $envValue;
        }

        if ($dbField) {
            return (string) ($this->settings()?->{$dbField} ?? '');
        }

        return '';
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->controllerId() !== ''
            && $this->baseUrl() !== '';
    }

    private function baseUrl(): string
    {
        return rtrim($this->resolve('url', 'controller_url'), '/');
    }

    private function controllerId(): string
    {
        return $this->resolve('controller_id', 'omada_id');
    }

    private function siteId(): string
    {
        return $this->resolve('site_id', 'site_id');
    }

    /**
     * Omada site ID for API calls: workspace first, then legacy .env / DB.
     */
    private function resolvedSiteId(?Workspace $workspace): string
    {
        if ($workspace !== null && $workspace->omada_site_id !== null && $workspace->omada_site_id !== '') {
            return $workspace->omada_site_id;
        }

        return $this->siteId();
    }

    private function clientId(): string
    {
        return $this->resolve('client_id', 'api_key');
    }

    private function clientSecret(): string
    {
        return $this->resolve('client_secret');
    }

    private function verifySsl(): bool
    {
        return (bool) config('services.omada.verify_ssl', false);
    }

    private function configuredClientId(): string
    {
        return (string) config('services.omada.client_id', '');
    }

    private function configuredClientSecret(): string
    {
        return (string) config('services.omada.client_secret', '');
    }

    private function configuredControllerId(): string
    {
        return (string) config('services.omada.controller_id', '');
    }

    private function configuredDefaultSiteId(): string
    {
        return (string) config('services.omada.site_id', '');
    }

    private function hasSavedControllerConnection(): bool
    {
        $settings = $this->settings();

        return $settings !== null
            && filled($settings->controller_url)
            && filled($settings->username);
    }

    /**
     * Step 1 audit: summarize which Omada capabilities are already implemented,
     * which still need configuration, and which remain unverified.
     *
     * @return array<int, array{title: string, status: string, description: string}>
     */
    public function auditCapabilities(): array
    {
        $hasLegacyControllerAccess = $this->hasSavedControllerConnection();
        $hasOpenApiClientId = filled($this->configuredClientId());
        $hasOpenApiClientSecret = filled($this->configuredClientSecret());
        $hasControllerId = filled($this->configuredControllerId()) || filled($this->settings()?->omada_id);
        $hasDefaultSiteId = filled($this->configuredDefaultSiteId()) || filled($this->settings()?->site_id);

        return [
            [
                'title' => 'Workspace site provisioning',
                'status' => $hasOpenApiClientId && $hasOpenApiClientSecret && $hasControllerId ? 'implemented' : ($hasLegacyControllerAccess ? 'needs_config' : 'setup_needed'),
                'description' => $hasOpenApiClientId && $hasOpenApiClientSecret && $hasControllerId
                    ? 'createSiteForBrand and the provisioning job are ready to create per-workspace Omada sites.'
                    : 'Provisioning code exists, but Open API automation still depends on OMADA_CLIENT_ID, OMADA_CLIENT_SECRET, and a controller ID.',
            ],
            [
                'title' => 'Device sync, rename, and reboot',
                'status' => $hasControllerId && $hasDefaultSiteId ? 'implemented' : ($hasLegacyControllerAccess ? 'needs_config' : 'setup_needed'),
                'description' => $hasControllerId && $hasDefaultSiteId
                    ? 'Device sync and AP management endpoints are already implemented in the service layer.'
                    : 'Management endpoints are coded, but they need a controller ID plus a site ID before they can be trusted in production.',
            ],
            [
                'title' => 'External portal authorize / unauthorize',
                'status' => $hasControllerId && $hasDefaultSiteId ? 'implemented' : ($hasLegacyControllerAccess ? 'needs_config' : 'setup_needed'),
                'description' => $hasControllerId && $hasDefaultSiteId
                    ? 'Hotspot external portal auth and unauth calls are already used by the payment/session flow.'
                    : 'The portal auth flow exists, but site-aware verification is still required for a real controller.',
            ],
            [
                'title' => 'Pending device adopt / assign to site',
                'status' => $hasControllerId ? 'needs_config' : 'unverified',
                'description' => $hasControllerId
                    ? 'Public Open API adopt and move endpoints are verified, but safe automation still needs per-device credentials and an admin workflow before SKY should trigger them.'
                    : 'Public Open API adopt and move endpoints are verified, but controller-aware configuration is still required before SKY can use them safely.',
            ],
            [
                'title' => 'Hotspot profile / portal URL automation',
                'status' => 'unverified',
                'description' => 'The app can supply the external portal URL, but auto-pushing it into Omada hotspot config still needs API confirmation.',
            ],
        ];
    }

    /**
     * Step 1 audit notes for the admin UI.
     *
     * @return array<int, string>
     */
    public function auditNotes(): array
    {
        return [
            'Step 1 confirms what is truly implemented in code today versus what still depends on your real controller API version.',
            'Admin screen credentials help with controller visibility, but Open API provisioning still relies on OMADA_* environment values for full automation.',
            'Adopt and move endpoints are now verified from the public Open API spec, but safe automation still depends on collecting device credentials and enforcing an admin-first workflow.',
        ];
    }

    /**
     * Step 1 automation readiness checklist.
     *
     * @return array<int, array{label: string, ready: bool, source: string}>
     */
    public function automationReadiness(?string $externalPortalUrl = null): array
    {
        $settings = $this->settings();

        return [
            [
                'label' => 'Controller URL saved',
                'ready' => filled($settings?->controller_url),
                'source' => 'Admin settings',
            ],
            [
                'label' => 'Controller username saved',
                'ready' => filled($settings?->username),
                'source' => 'Admin settings',
            ],
            [
                'label' => 'Open API client ID available',
                'ready' => filled($this->configuredClientId()),
                'source' => '.env / config',
            ],
            [
                'label' => 'Open API client secret available',
                'ready' => filled($this->configuredClientSecret()),
                'source' => '.env / config',
            ],
            [
                'label' => 'Controller ID available',
                'ready' => filled($this->configuredControllerId()) || filled($settings?->omada_id),
                'source' => '.env or detected from controller',
            ],
            [
                'label' => 'Default site ID available',
                'ready' => filled($this->configuredDefaultSiteId()) || filled($settings?->site_id),
                'source' => '.env or saved fallback site',
            ],
            [
                'label' => 'External portal URL available',
                'ready' => filled($externalPortalUrl) || filled($settings?->external_portal_url),
                'source' => 'Admin settings / detected public URL',
            ],
        ];
    }

    /**
     * Final workspace readiness checks before the signed-in workspace can use
     * Omada-powered portal and device actions end-to-end.
     *
     * @return array<int, array{label: string, ready: bool, source: string}>
     */
    public function finalizeSiteReadiness(?Workspace $workspace = null, ?string $externalPortalUrl = null): array
    {
        $settings = $this->settings();

        return [
            [
                'label' => 'Controller connection verified',
                'ready' => (bool) ($settings?->is_connected),
                'source' => 'Connection test',
            ],
            [
                'label' => 'Open API automation configured',
                'ready' => $this->isConfigured(),
                'source' => '.env / config',
            ],
            [
                'label' => 'Workspace Omada site assigned',
                'ready' => filled($workspace?->omada_site_id),
                'source' => 'Workspace provisioning',
            ],
            [
                'label' => 'Controller ID available',
                'ready' => filled($this->configuredControllerId()) || filled($settings?->omada_id),
                'source' => '.env or detected from controller',
            ],
            [
                'label' => 'External portal URL saved',
                'ready' => filled($externalPortalUrl) || filled($settings?->external_portal_url),
                'source' => 'Admin settings / detected public URL',
            ],
            [
                'label' => 'Hotspot operator credentials saved',
                'ready' => filled($settings?->hotspot_operator_name) && filled($settings?->hotspot_operator_password),
                'source' => 'Admin settings',
            ],
        ];
    }

    public function deviceAdoptionStatus(?Workspace $workspace = null): array
    {
        $settings = $this->settings();
        $blockers = [];

        if (! $this->isConfigured()) {
            $blockers[] = 'Open API automation is not fully configured yet.';
        }

        if (! (bool) ($settings?->is_connected)) {
            $blockers[] = 'Controller connection has not been verified yet.';
        }

        if (! filled($workspace?->omada_site_id)) {
            $blockers[] = 'This workspace does not have an Omada site assigned yet.';
        }

        if ($blockers !== []) {
            return [
                'status' => 'blocked',
                'badge_color' => 'amber',
                'title' => 'Finish workspace readiness before Step 3 device adoption',
                'message' => 'Device adoption still begins in the Omada controller UI, but this workspace needs the core readiness checks completed before site-aware validation can be trusted.',
                'action_label' => 'Complete readiness first',
                'steps' => [
                    'Finish workspace provisioning and confirm the Omada site ID is assigned.',
                    'Verify the controller connection from the Omada Integration page.',
                    'Return here after readiness is complete to continue the manual adoption flow.',
                ],
                'blockers' => $blockers,
                'endpoint_verified' => false,
            ];
        }

        return [
            'status' => 'manual',
            'badge_color' => 'sky',
            'title' => 'Adopt endpoint is verified, but device credentials are still required',
            'message' => 'The public Open API now verifies start-adopt, adopt-result, and site-move endpoints. SKY still keeps Step 3 manual-first until an admin provides the device credentials needed to trigger adoption safely.',
            'action_label' => 'Prepare device credentials, then adopt',
            'steps' => [
                'Confirm the pending device default username and password before starting adoption.',
                'Open the Omada controller and adopt the pending device under the correct workspace site.',
                'Return to SKY and click Sync from Omada to import the device into this workspace.',
            ],
            'blockers' => [],
            'endpoint_verified' => true,
        ];
    }

    /**
     * @return array{status: string, supported: bool, total: int, isolated: array<int, array{name: string, mac: string, model: string|null, type: string|null, in_sky: bool, local_device_name: string|null, local_device_status: string|null}>, preconfig: array<int, array{name: string, mac: string, model: string|null, type: string|null, in_sky: bool, local_device_name: string|null, local_device_status: string|null}>, correlation: array{already_in_sky: int, not_in_sky: int}, error: string|null}
     */
    public function pendingDeviceInventory(?Workspace $workspace = null): array
    {
        $settings = $this->settings();

        if (! $this->isConfigured() || ! (bool) ($settings?->is_connected) || ! filled($workspace?->omada_site_id)) {
            return [
                'status' => 'blocked',
                'supported' => false,
                'total' => 0,
                'isolated' => [],
                'preconfig' => [],
                'correlation' => ['already_in_sky' => 0, 'not_in_sky' => 0],
                'error' => null,
            ];
        }

        $cacheKey = $this->pendingDeviceInventoryCacheKey($workspace);

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($workspace): array {
            if (! $this->ensureAuthenticated()) {
                return [
                    'status' => 'unavailable',
                    'supported' => true,
                    'total' => 0,
                    'isolated' => [],
                    'preconfig' => [],
                    'correlation' => ['already_in_sky' => 0, 'not_in_sky' => 0],
                    'error' => 'Failed to authenticate with Omada controller',
                ];
            }

            $url = $this->baseUrl().'/openapi/v2/'.$this->controllerId().'/sites/'.$this->resolvedSiteId($workspace).'/topology/isolated-and-pre-config';

            try {
                $response = $this->httpClient()->get($url);

                if (! $response->successful() || $response->json('errorCode') !== 0) {
                    return [
                        'status' => 'unavailable',
                        'supported' => true,
                        'total' => 0,
                        'isolated' => [],
                        'preconfig' => [],
                        'correlation' => ['already_in_sky' => 0, 'not_in_sky' => 0],
                        'error' => $response->json('msg') ?: 'Unable to fetch pending device inventory from Omada.',
                    ];
                }

                $result = $response->json('result') ?? [];
                $localDeviceLookup = $this->localDeviceLookup($workspace);
                $isolated = $this->normalizeTopologyBriefDevices($result['isolated'] ?? [], $localDeviceLookup);
                $preconfig = $this->normalizeTopologyBriefDevices($result['preconfig'] ?? [], $localDeviceLookup);
                $allDevices = [...$isolated, ...$preconfig];

                return [
                    'status' => 'ready',
                    'supported' => true,
                    'total' => (int) ($result['total'] ?? 0),
                    'isolated' => $isolated,
                    'preconfig' => $preconfig,
                    'correlation' => [
                        'already_in_sky' => count(array_filter($allDevices, fn (array $device): bool => $device['in_sky'])),
                        'not_in_sky' => count(array_filter($allDevices, fn (array $device): bool => ! $device['in_sky'])),
                    ],
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $this->log('warning', 'Pending device inventory fetch failed', [
                    'workspace_id' => $workspace->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'status' => 'unavailable',
                    'supported' => true,
                    'total' => 0,
                    'isolated' => [],
                    'preconfig' => [],
                    'correlation' => ['already_in_sky' => 0, 'not_in_sky' => 0],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    public function forgetPendingDeviceInventory(?Workspace $workspace = null): void
    {
        if ($workspace === null || ! filled($workspace->omada_site_id)) {
            return;
        }

        Cache::forget($this->pendingDeviceInventoryCacheKey($workspace));
    }

    private function sitesUrl(): string
    {
        return $this->baseUrl().'/openapi/v1/'.$this->controllerId().'/sites';
    }

    private function pendingDeviceInventoryCacheKey(Workspace $workspace): string
    {
        return 'omada_pending_device_inventory:'.$workspace->id.':'.$workspace->omada_site_id;
    }

    /**
     * @return array<string, array{name: string, status: string}>
     */
    private function localDeviceLookup(Workspace $workspace): array
    {
        return Device::query()
            ->where('workspace_id', $workspace->id)
            ->get(['ap_mac', 'name', 'status'])
            ->mapWithKeys(function (Device $device): array {
                return [
                    strtoupper((string) $device->ap_mac) => [
                        'name' => $device->name,
                        'status' => $device->status,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $devices
     * @param  array<string, array{name: string, status: string}>  $localDeviceLookup
     * @return array<int, array{name: string, mac: string, model: string|null, type: string|null, in_sky: bool, local_device_name: string|null, local_device_status: string|null}>
     */
    private function normalizeTopologyBriefDevices(array $devices, array $localDeviceLookup = []): array
    {
        return array_values(array_map(function (array $device) use ($localDeviceLookup): array {
            $mac = strtoupper(str_replace('-', ':', (string) ($device['mac'] ?? '')));
            $name = (string) ($device['name'] ?? $device['model'] ?? $mac ?: 'Pending device');
            $localDevice = $localDeviceLookup[$mac] ?? null;

            return [
                'name' => $name,
                'mac' => $mac,
                'model' => isset($device['model']) ? (string) $device['model'] : null,
                'type' => isset($device['type']) ? (string) $device['type'] : null,
                'in_sky' => $localDevice !== null,
                'local_device_name' => $localDevice['name'] ?? null,
                'local_device_status' => $localDevice['status'] ?? null,
            ];
        }, $devices));
    }

    private function extractSiteId(mixed $result): ?string
    {
        if (! is_array($result)) {
            return null;
        }

        $siteId = $result['siteId'] ?? $result['siteID'] ?? $result['id'] ?? null;

        return filled($siteId) ? (string) $siteId : null;
    }

    private function findExistingSiteIdByName(string $siteDisplayName): ?string
    {
        try {
            $response = $this->httpClient()->get($this->sitesUrl().'?page=1&pageSize=100');

            if (! $response->successful()) {
                return null;
            }

            $sites = $response->json('result.data') ?? [];
            $expectedName = mb_strtolower(trim($siteDisplayName));

            foreach ($sites as $site) {
                $siteName = mb_strtolower(trim((string) ($site['name'] ?? '')));

                if ($siteName !== '' && $siteName === $expectedName) {
                    return $this->extractSiteId($site);
                }
            }
        } catch (\Exception $e) {
            $this->log('warning', 'Find existing Omada site failed', [
                'name' => $siteDisplayName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function buildSiteCreateFailure(int $status, string $errorMessage): array
    {
        $normalizedError = trim($errorMessage) !== '' ? trim($errorMessage) : 'Unknown error creating Omada site';
        $lowerError = strtolower($normalizedError);
        $errorCode = 'site_create_failed';
        $retryable = false;

        if ($status === 0) {
            $errorCode = 'controller_exception';
            $retryable = true;
        } elseif ($status === 429) {
            $errorCode = 'rate_limited';
            $retryable = true;
        } elseif (in_array($status, [500, 502, 503, 504], true)) {
            $errorCode = 'controller_unavailable';
            $retryable = true;
        } elseif (in_array($status, [401, 403], true)) {
            $errorCode = 'authentication_failed';
        } elseif ($status === 409 || str_contains($lowerError, 'already exist') || str_contains($lowerError, 'duplicate')) {
            $errorCode = 'duplicate_site';
        }

        return [
            'success' => false,
            'siteId' => null,
            'error' => $normalizedError,
            'error_code' => $errorCode,
            'retryable' => $retryable,
        ];
    }

    // ─── Authentication (Open API Only) ─────────────────────────

    /**
     * Authenticate via Open API Client Credentials grant.
     * Token is cached for 50 minutes.
     */
    public function authenticate(): bool
    {
        // 1. Try cache first
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if ($cached) {
            $this->accessToken = $cached['accessToken'];
            $this->csrfToken = $cached['csrfToken'] ?? null;

            $this->log('info', 'Using cached access token');

            return true;
        }

        // 2. Request new token
        $clientId = $this->clientId();
        $clientSecret = $this->clientSecret();

        if ($clientId === '' || $clientSecret === '') {
            $this->log('error', 'Open API credentials not configured', [
                'has_client_id' => $clientId !== '',
                'has_client_secret' => $clientSecret !== '',
            ]);

            return false;
        }

        try {
            $baseUrl = $this->baseUrl();
            $controllerId = $this->controllerId();

            $this->log('info', 'Requesting Open API token', [
                'url' => "{$baseUrl}/openapi/authorize/token",
                'controller_id' => $controllerId,
            ]);

            $response = Http::timeout(15)
                ->when(! $this->verifySsl(), fn ($http) => $http->withoutVerifying())
                ->post("{$baseUrl}/openapi/authorize/token?grant_type=client_credentials", [
                    'omadacId' => $controllerId,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json('result') ?? $response->json();
                $this->accessToken = $data['accessToken'] ?? null;
                $this->csrfToken = $data['csrfToken'] ?? null;

                if ($this->accessToken) {
                    Cache::put(self::TOKEN_CACHE_KEY, [
                        'accessToken' => $this->accessToken,
                        'csrfToken' => $this->csrfToken,
                    ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

                    $this->log('info', 'Open API token acquired and cached');

                    return true;
                }
            }

            $this->log('warning', 'Open API authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            $this->log('error', 'Open API authentication exception', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Ensure we have a valid access token.
     */
    private function ensureAuthenticated(): bool
    {
        return $this->accessToken !== null || $this->authenticate();
    }

    /**
     * Clear cached token (force re-auth on next call).
     */
    public function flushToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
        $this->accessToken = null;
        $this->csrfToken = null;

        $this->log('info', 'Token cache flushed');
    }

    // ─── HTTP Client ────────────────────────────────────────────

    /**
     * Build an authenticated HTTP client with Open API headers.
     */
    private function httpClient(): PendingRequest
    {
        return Http::timeout(15)
            ->when(! $this->verifySsl(), fn ($http) => $http->withoutVerifying())
            ->withHeaders(array_filter([
                'Authorization' => 'AccessToken='.$this->accessToken,
                'Csrf-Token' => $this->csrfToken,
            ]));
    }

    /**
     * Build the Open API URL for a given endpoint path.
     */
    private function apiUrl(string $path, ?Workspace $workspace = null): string
    {
        $base = $this->baseUrl();
        $cid = $this->controllerId();
        $sid = $this->resolvedSiteId($workspace);

        return "{$base}/openapi/v1/{$cid}/sites/{$sid}/{$path}";
    }

    // ─── Client Authorization ───────────────────────────────────

    /**
     * Authorize a client MAC on the Omada hotspot.
     *
     * @param  array{clientMac: string, apMac?: string, ssid?: string, minutes?: int}  $data
     * @return array{success: bool, authId: string|null, error: string|null}
     */
    public function authorizeClient(array $data, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'authId' => null, 'error' => 'Omada site is not configured for this workspace'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'authId' => null, 'error' => 'Failed to authenticate with Omada controller'];
        }

        // Rate limit Omada API calls
        if (RateLimiter::tooManyAttempts('omada-api', 30)) {
            $this->log('warning', 'Rate limit exceeded for Omada API');

            return ['success' => false, 'authId' => null, 'error' => 'Omada API rate limit exceeded'];
        }

        RateLimiter::hit('omada-api');

        $minutes = $data['minutes'] ?? 60;
        $payload = [
            'clientMac' => $data['clientMac'],
            'apMac' => $data['apMac'] ?? '',
            'ssidName' => $data['ssid'] ?? '',
            'authType' => 4,
            'time' => $minutes * 60,
        ];

        $url = $this->apiUrl('hotspot/extPortal/auth', $workspace);

        $this->log('info', 'Authorizing client', [
            'url' => $url,
            'clientMac' => $data['clientMac'],
            'minutes' => $minutes,
        ]);

        try {
            $response = $this->httpClient()->post($url, $payload);

            if ($response->successful() && $response->json('errorCode') === 0) {
                $authId = $response->json('result.clientId') ?? $response->json('result.id');

                $this->log('info', 'Client authorized successfully', [
                    'clientMac' => $data['clientMac'],
                    'minutes' => $minutes,
                    'authId' => $authId,
                ]);

                return ['success' => true, 'authId' => $authId, 'error' => null];
            }

            // Token may have expired — retry once with fresh token
            if ($response->status() === 401) {
                $this->log('warning', 'Token expired during authorize, refreshing');
                $this->flushToken();

                if ($this->authenticate()) {
                    $response = $this->httpClient()->post($this->apiUrl('hotspot/extPortal/auth', $workspace), $payload);

                    if ($response->successful() && $response->json('errorCode') === 0) {
                        $authId = $response->json('result.clientId') ?? $response->json('result.id');

                        $this->log('info', 'Client authorized on retry', [
                            'clientMac' => $data['clientMac'],
                            'authId' => $authId,
                        ]);

                        return ['success' => true, 'authId' => $authId, 'error' => null];
                    }
                }
            }

            $errorMsg = $response->json('msg') ?? $response->body();

            $this->log('warning', 'Authorization failed', [
                'clientMac' => $data['clientMac'],
                'status' => $response->status(),
                'error' => $errorMsg,
            ]);

            return ['success' => false, 'authId' => null, 'error' => "Authorization failed: {$errorMsg}"];
        } catch (\Exception $e) {
            $this->log('error', 'Authorization exception', [
                'clientMac' => $data['clientMac'],
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'authId' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Unauthorize (disconnect) a client from the hotspot.
     *
     * @return array{success: bool, error: string|null}
     */
    public function unauthorizeClient(string $clientMac, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'error' => 'Omada site is not configured for this workspace'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        if (RateLimiter::tooManyAttempts('omada-api', 30)) {
            return ['success' => false, 'error' => 'Omada API rate limit exceeded'];
        }

        RateLimiter::hit('omada-api');

        $url = $this->apiUrl('hotspot/extPortal/unauth', $workspace);

        $this->log('info', 'Unauthorizing client', [
            'url' => $url,
            'clientMac' => $clientMac,
        ]);

        try {
            $response = $this->httpClient()->post($url, ['clientMac' => $clientMac]);

            if ($response->successful() && $response->json('errorCode') === 0) {
                $this->log('info', 'Client unauthorized successfully', ['clientMac' => $clientMac]);

                return ['success' => true, 'error' => null];
            }

            // Retry on token expiry
            if ($response->status() === 401) {
                $this->flushToken();

                if ($this->authenticate()) {
                    $response = $this->httpClient()->post($this->apiUrl('hotspot/extPortal/unauth', $workspace), ['clientMac' => $clientMac]);

                    if ($response->successful() && $response->json('errorCode') === 0) {
                        $this->log('info', 'Client unauthorized on retry', ['clientMac' => $clientMac]);

                        return ['success' => true, 'error' => null];
                    }
                }
            }

            $errorMsg = $response->json('msg') ?? $response->body();

            $this->log('warning', 'Unauthorization failed', [
                'clientMac' => $clientMac,
                'error' => $errorMsg,
            ]);

            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            $this->log('error', 'Unauthorization exception', [
                'clientMac' => $clientMac,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new Omada site (maps to a customer "brand" in SKY Omada).
     *
     * @return array{success: bool, siteId: string|null, error: string|null}
     */
    public function createSiteForBrand(string $siteDisplayName): array
    {
        if (! $this->ensureAuthenticated()) {
            return $this->buildSiteCreateFailure(401, 'Failed to authenticate with Omada controller');
        }

        $url = $this->sitesUrl();

        $this->log('info', 'Creating Omada site', ['url' => $url, 'name' => $siteDisplayName]);

        try {
            $response = $this->httpClient()->post($url, ['name' => $siteDisplayName]);

            if ($response->successful() && $response->json('errorCode') === 0) {
                $siteId = $this->extractSiteId($response->json('result'));

                if ($siteId) {
                    $this->log('info', 'Omada site created', ['siteId' => $siteId]);

                    return ['success' => true, 'siteId' => (string) $siteId, 'error' => null, 'error_code' => null, 'retryable' => false];
                }
            }

            if ($response->status() === 401) {
                $this->flushToken();

                if ($this->authenticate()) {
                    $response = $this->httpClient()->post($url, ['name' => $siteDisplayName]);

                    if ($response->successful() && $response->json('errorCode') === 0) {
                        $siteId = $this->extractSiteId($response->json('result'));

                        if ($siteId) {
                            return ['success' => true, 'siteId' => (string) $siteId, 'error' => null, 'error_code' => null, 'retryable' => false];
                        }
                    }
                }
            }

            $errorMsg = $response->json('msg') ?? $response->body();

            if (($response->status() === 409 || str_contains(strtolower((string) $errorMsg), 'already exist') || str_contains(strtolower((string) $errorMsg), 'duplicate'))
                && ($existingSiteId = $this->findExistingSiteIdByName($siteDisplayName))) {
                $this->log('info', 'Using existing Omada site after duplicate response', [
                    'name' => $siteDisplayName,
                    'siteId' => $existingSiteId,
                ]);

                return ['success' => true, 'siteId' => $existingSiteId, 'error' => null, 'error_code' => null, 'retryable' => false];
            }

            return $this->buildSiteCreateFailure($response->status(), (string) $errorMsg);
        } catch (\Exception $e) {
            $this->log('error', 'Create site exception', ['error' => $e->getMessage()]);

            return $this->buildSiteCreateFailure(0, $e->getMessage());
        }
    }

    /**
     * Sync devices for every workspace that has an Omada site provisioned.
     *
     * @return array{success: bool, workspaces: int, synced: int, error: string|null}
     */
    public function syncDevicesForAllWorkspaces(): array
    {
        $workspaces = Workspace::query()
            ->where('provisioning_status', 'ready')
            ->whereNotNull('omada_site_id')
            ->get();

        $synced = 0;

        foreach ($workspaces as $workspace) {
            $one = $this->syncDevicesFromOmada($workspace);
            if ($one['success']) {
                $workspace->forceFill(['devices_last_synced_at' => now()])->save();
                $synced += $one['synced'];
            }
        }

        return [
            'success' => true,
            'workspaces' => $workspaces->count(),
            'synced' => $synced,
            'error' => null,
        ];
    }

    // ─── Device Sync ────────────────────────────────────────────

    /**
     * Sync AP devices from Omada controller into local DB.
     * Pulls rich details: firmware, clients, uptime, radio channels, tx power.
     *
     * @return array{success: bool, synced: int, error: string|null}
     */
    public function syncDevicesFromOmada(Workspace $workspace): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'synced' => 0, 'error' => 'Workspace has no Omada site ID yet'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'synced' => 0, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl('devices', $workspace).'?type=ap&page=1&pageSize=100';

        $this->log('info', 'Starting device sync', ['url' => $url, 'workspace_id' => $workspace->id]);

        try {
            // Fetch site name for display
            $siteName = $this->fetchSiteName($workspace);

            $response = $this->httpClient()->get($url);

            if (! $response->successful()) {
                $this->log('warning', 'Device sync API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'synced' => 0, 'error' => "API request failed: HTTP {$response->status()}"];
            }

            $devices = $response->json('result.data') ?? $response->json('result') ?? [];
            $synced = 0;

            foreach ($devices as $ap) {
                $mac = $ap['mac'] ?? null;

                if (! $mac) {
                    continue;
                }

                // Normalize MAC: dashes → colons, uppercase
                $mac = strtoupper(str_replace('-', ':', $mac));

                Device::updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'ap_mac' => $mac,
                    ],
                    [
                        'name' => $this->resolveDeviceName($ap),
                        'omada_device_id' => $ap['deviceId'] ?? $ap['id'] ?? null,
                        'model' => $ap['model'] ?? $ap['showModel'] ?? null,
                        'firmware_version' => $ap['firmwareVersion'] ?? $ap['version'] ?? null,
                        'ip_address' => $ap['ip'] ?? null,
                        'site_name' => $ap['site'] ?? $siteName ?? $this->resolvedSiteId($workspace),
                        'clients_count' => $ap['clientNum'] ?? $ap['clients'] ?? 0,
                        'uptime_seconds' => $this->parseUptime($ap['uptimeLong'] ?? $ap['uptime'] ?? 0),
                        'channel_2g' => $this->extractRadioField($ap, '2g', 'channel'),
                        'channel_5g' => $this->extractRadioField($ap, '5g', 'channel'),
                        'tx_power_2g' => $this->extractRadioField($ap, '2g', 'txPower'),
                        'tx_power_5g' => $this->extractRadioField($ap, '5g', 'txPower'),
                        'status' => in_array($ap['status'] ?? 0, [2, 14]) ? 'online' : 'offline',
                        'last_seen_at' => isset($ap['lastSeen'])
                            ? Carbon::createFromTimestamp($ap['lastSeen'] / 1000)
                            : now(),
                    ]
                );
                $synced++;
            }

            $this->log('info', 'Device sync completed', [
                'synced' => $synced,
                'total_from_api' => count($devices),
            ]);

            return ['success' => true, 'synced' => $synced, 'error' => null];
        } catch (\Exception $e) {
            $this->log('error', 'Device sync exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'synced' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resolve a friendly device name from the API data.
     * Omada defaults the name to the MAC address — use modelName instead when that happens.
     */
    private function resolveDeviceName(array $ap): string
    {
        $name = $ap['name'] ?? '';
        $mac = $ap['mac'] ?? '';

        // If the name looks like a MAC address, use modelName or model instead
        if ($name === '' || $name === $mac || preg_match('/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/', $name)) {
            return $ap['modelName'] ?? $ap['model'] ?? $name ?: 'Unknown AP';
        }

        return $name;
    }

    /**
     * Fetch the site name for the configured site ID.
     */
    private function fetchSiteName(?Workspace $workspace = null): ?string
    {
        try {
            $url = $this->baseUrl()."/openapi/v1/{$this->controllerId()}/sites?page=1&pageSize=100";
            $response = $this->httpClient()->get($url);

            if ($response->successful()) {
                $sites = $response->json('result.data') ?? [];
                $siteId = $this->resolvedSiteId($workspace);

                foreach ($sites as $site) {
                    if (($site['siteId'] ?? '') === $siteId) {
                        return $site['name'] ?? null;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->log('warning', 'Failed to fetch site name', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Extract a radio field (channel/txPower) from the AP data.
     * Omada returns radio info in various formats depending on firmware version.
     */
    private function extractRadioField(array $ap, string $band, string $field): ?string
    {
        // Format 1: radioSetting array with radioband key
        $bandKey = $band === '2g' ? 0 : 1;
        $radios = $ap['radioSetting'] ?? $ap['radios'] ?? [];

        foreach ($radios as $radio) {
            $radioBand = $radio['radioBand'] ?? $radio['band'] ?? null;
            $matchBand = $band === '2g' ? '2.4G' : '5G';

            if ($radioBand === $matchBand || ($radio['index'] ?? null) === $bandKey) {
                return isset($radio[$field]) ? (string) $radio[$field] : null;
            }
        }

        // Format 2: flat keys like channel2g / channel5g
        $flatKey = $field.($band === '2g' ? '2g' : '5g');

        return isset($ap[$flatKey]) ? (string) $ap[$flatKey] : null;
    }

    /**
     * Parse uptime value into seconds.
     * Omada may return an integer (seconds) or a string like "58m 28s", "2h 30m 15s", "3d 4h".
     */
    private function parseUptime(mixed $uptime): int
    {
        if (is_numeric($uptime)) {
            return (int) $uptime;
        }

        if (! is_string($uptime)) {
            return 0;
        }

        $seconds = 0;

        if (preg_match('/(\d+)\s*d/', $uptime, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)\s*h/', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)\s*m/', $uptime, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*s/', $uptime, $m)) {
            $seconds += (int) $m[1];
        }

        return $seconds;
    }

    // ─── Device Config Push ─────────────────────────────────────

    /**
     * Rename a device on the Omada controller.
     *
     * @return array{success: bool, error: string|null}
     */
    public function renameDevice(string $deviceMac, string $newName, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'error' => 'Omada site is not configured for this workspace'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl("devices/{$deviceMac}", $workspace);

        $this->log('info', 'Renaming device on Omada', [
            'mac' => $deviceMac,
            'newName' => $newName,
        ]);

        try {
            $response = $this->httpClient()->patch($url, ['name' => $newName]);

            if ($response->successful() && $response->json('errorCode') === 0) {
                $this->log('info', 'Device renamed successfully', ['mac' => $deviceMac]);

                return ['success' => true, 'error' => null];
            }

            // Retry on token expiry
            if ($response->status() === 401) {
                $this->flushToken();

                if ($this->authenticate()) {
                    $response = $this->httpClient()->patch($this->apiUrl("devices/{$deviceMac}", $workspace), ['name' => $newName]);

                    if ($response->successful() && $response->json('errorCode') === 0) {
                        return ['success' => true, 'error' => null];
                    }
                }
            }

            $errorMsg = $response->json('msg') ?? $response->body();

            $this->log('warning', 'Device rename failed', [
                'mac' => $deviceMac,
                'error' => $errorMsg,
            ]);

            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            $this->log('error', 'Device rename exception', [
                'mac' => $deviceMac,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reboot a device on the Omada controller.
     *
     * @return array{success: bool, error: string|null}
     */
    public function rebootDevice(string $deviceMac, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'error' => 'Omada site is not configured for this workspace'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl("devices/{$deviceMac}/reboot", $workspace);

        $this->log('info', 'Rebooting device', ['mac' => $deviceMac]);

        try {
            $response = $this->httpClient()->post($url);

            if ($response->successful() && $response->json('errorCode') === 0) {
                $this->log('info', 'Device reboot initiated', ['mac' => $deviceMac]);

                return ['success' => true, 'error' => null];
            }

            $errorMsg = $response->json('msg') ?? $response->body();

            $this->log('warning', 'Device reboot failed', ['mac' => $deviceMac, 'error' => $errorMsg]);

            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            $this->log('error', 'Device reboot exception', ['mac' => $deviceMac, 'error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function startAdoptDevice(string $deviceMac, string $username, string $password, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return ['success' => false, 'error' => 'Omada site is not configured for this workspace'];
        }

        if (trim($username) === '' || trim($password) === '') {
            return ['success' => false, 'error' => 'Device adoption credentials are required'];
        }

        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $formattedMac = $this->formatOmadaDeviceMac($deviceMac, '-');
        $url = $this->apiUrl("devices/{$formattedMac}/start-adopt", $workspace);

        try {
            $response = $this->httpClient()->post($url, [
                'username' => $username,
                'password' => $password,
            ]);

            if ($response->successful() && $response->json('errorCode') === 0) {
                return ['success' => true, 'error' => null];
            }

            return ['success' => false, 'error' => $response->json('msg') ?? $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAdoptDeviceResult(string $deviceMac, ?Workspace $workspace = null): array
    {
        if ($this->resolvedSiteId($workspace) === '') {
            return [
                'success' => false,
                'adopted' => false,
                'device_mac' => null,
                'adopt_error_code' => null,
                'adopt_failed_type' => null,
                'error' => 'Omada site is not configured for this workspace',
            ];
        }

        if (! $this->ensureAuthenticated()) {
            return [
                'success' => false,
                'adopted' => false,
                'device_mac' => null,
                'adopt_error_code' => null,
                'adopt_failed_type' => null,
                'error' => 'Failed to authenticate with Omada controller',
            ];
        }

        $formattedMac = $this->formatOmadaDeviceMac($deviceMac, '-');
        $url = $this->apiUrl("devices/{$formattedMac}/adopt-result", $workspace);

        try {
            $response = $this->httpClient()->get($url);

            if (! $response->successful() || $response->json('errorCode') !== 0) {
                return [
                    'success' => false,
                    'adopted' => false,
                    'device_mac' => null,
                    'adopt_error_code' => null,
                    'adopt_failed_type' => null,
                    'error' => $response->json('msg') ?? $response->body(),
                ];
            }

            $result = $response->json('result') ?? [];
            $adoptErrorCode = isset($result['adoptErrorCode']) ? (int) $result['adoptErrorCode'] : null;
            $adoptFailedType = isset($result['adoptFailedType']) ? (int) $result['adoptFailedType'] : null;

            return [
                'success' => $adoptErrorCode === 0,
                'adopted' => $adoptErrorCode === 0,
                'device_mac' => isset($result['deviceMac']) ? $this->formatOmadaDeviceMac((string) $result['deviceMac']) : null,
                'adopt_error_code' => $adoptErrorCode,
                'adopt_failed_type' => $adoptFailedType,
                'error' => $adoptErrorCode === 0 ? null : $this->adoptErrorMessage($adoptErrorCode, $adoptFailedType),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'adopted' => false,
                'device_mac' => null,
                'adopt_error_code' => null,
                'adopt_failed_type' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function formatOmadaDeviceMac(string $deviceMac, string $separator = ':'): string
    {
        $segments = str_split(strtoupper(preg_replace('/[^A-F0-9]/i', '', $deviceMac) ?? ''), 2);

        return implode($separator, array_filter($segments));
    }

    private function adoptErrorMessage(?int $adoptErrorCode, ?int $adoptFailedType = null): string
    {
        return match ($adoptErrorCode) {
            -39002 => 'Device adoption failed because the device did not respond to adopt commands.',
            -39003 => 'Device adoption failed because the username or password is incorrect.',
            -39004 => 'Device adoption failed.',
            -39005 => 'Device adoption failed because the device is not connected.',
            -39329 => 'Device adoption failed because Omada could not link to the uplink AP.',
            default => $adoptFailedType === -2
                ? 'Device adoption failed and requires device username or password input.'
                : 'Device adoption failed.',
        };
    }

    // ─── Logging ────────────────────────────────────────────────

    /**
     * Log to the dedicated omada channel.
     *
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('omada')->{$level}("[OmadaService] {$message}", $context);
    }
}
