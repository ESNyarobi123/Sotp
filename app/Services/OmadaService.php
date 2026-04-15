<?php

namespace App\Services;

use App\Models\Device;
use App\Models\OmadaSetting;
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
                'Authorization' => 'AccessToken=' . $this->accessToken,
                'Csrf-Token' => $this->csrfToken,
            ]));
    }

    /**
     * Build the Open API URL for a given endpoint path.
     */
    private function apiUrl(string $path): string
    {
        $base = $this->baseUrl();
        $cid = $this->controllerId();
        $sid = $this->siteId();

        return "{$base}/openapi/v1/{$cid}/sites/{$sid}/{$path}";
    }

    // ─── Client Authorization ───────────────────────────────────

    /**
     * Authorize a client MAC on the Omada hotspot.
     *
     * @param  array{clientMac: string, apMac?: string, ssid?: string, minutes?: int}  $data
     * @return array{success: bool, authId: string|null, error: string|null}
     */
    public function authorizeClient(array $data): array
    {
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

        $url = $this->apiUrl('hotspot/extPortal/auth');

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
                    $response = $this->httpClient()->post($url, $payload);

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
    public function unauthorizeClient(string $clientMac): array
    {
        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        if (RateLimiter::tooManyAttempts('omada-api', 30)) {
            return ['success' => false, 'error' => 'Omada API rate limit exceeded'];
        }

        RateLimiter::hit('omada-api');

        $url = $this->apiUrl('hotspot/extPortal/unauth');

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
                    $response = $this->httpClient()->post($url, ['clientMac' => $clientMac]);

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

    // ─── Device Sync ────────────────────────────────────────────

    /**
     * Sync AP devices from Omada controller into local DB.
     * Pulls rich details: firmware, clients, uptime, radio channels, tx power.
     *
     * @return array{success: bool, synced: int, error: string|null}
     */
    public function syncDevicesFromOmada(): array
    {
        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'synced' => 0, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl('devices') . '?type=ap&page=1&pageSize=100';

        $this->log('info', 'Starting device sync', ['url' => $url]);

        try {
            // Fetch site name for display
            $siteName = $this->fetchSiteName();

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
                    ['ap_mac' => $mac],
                    [
                        'name' => $this->resolveDeviceName($ap),
                        'omada_device_id' => $ap['deviceId'] ?? $ap['id'] ?? null,
                        'model' => $ap['model'] ?? $ap['showModel'] ?? null,
                        'firmware_version' => $ap['firmwareVersion'] ?? $ap['version'] ?? null,
                        'ip_address' => $ap['ip'] ?? null,
                        'site_name' => $ap['site'] ?? $siteName ?? $this->siteId(),
                        'clients_count' => $ap['clientNum'] ?? $ap['clients'] ?? 0,
                        'uptime_seconds' => $this->parseUptime($ap['uptimeLong'] ?? $ap['uptime'] ?? 0),
                        'channel_2g' => $this->extractRadioField($ap, '2g', 'channel'),
                        'channel_5g' => $this->extractRadioField($ap, '5g', 'channel'),
                        'tx_power_2g' => $this->extractRadioField($ap, '2g', 'txPower'),
                        'tx_power_5g' => $this->extractRadioField($ap, '5g', 'txPower'),
                        'status' => in_array($ap['status'] ?? 0, [2, 14]) ? 'online' : 'offline',
                        'last_seen_at' => isset($ap['lastSeen'])
                            ? \Carbon\Carbon::createFromTimestamp($ap['lastSeen'] / 1000)
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
    private function fetchSiteName(): ?string
    {
        try {
            $url = $this->baseUrl() . "/openapi/v1/{$this->controllerId()}/sites?page=1&pageSize=100";
            $response = $this->httpClient()->get($url);

            if ($response->successful()) {
                $sites = $response->json('result.data') ?? [];
                $siteId = $this->siteId();

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
        $flatKey = $field . ($band === '2g' ? '2g' : '5g');

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
    public function renameDevice(string $deviceMac, string $newName): array
    {
        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl("devices/{$deviceMac}");

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
                    $response = $this->httpClient()->patch($url, ['name' => $newName]);

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
    public function rebootDevice(string $deviceMac): array
    {
        if (! $this->ensureAuthenticated()) {
            return ['success' => false, 'error' => 'Failed to authenticate with Omada controller'];
        }

        $url = $this->apiUrl("devices/{$deviceMac}/reboot");

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
