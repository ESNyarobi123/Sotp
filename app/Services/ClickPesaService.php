<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickPesaService
{
    private const BASE_URL = 'https://api.clickpesa.com/third-parties';

    private ?PaymentGatewaySetting $settings = null;

    /**
     * Get the ClickPesa gateway settings.
     */
    public function settings(): ?PaymentGatewaySetting
    {
        if ($this->settings === null) {
            $this->settings = PaymentGatewaySetting::where('gateway', 'clickpesa')
                ->where('is_active', true)
                ->first();
        }

        return $this->settings;
    }

    /**
     * Check if ClickPesa is configured and active.
     */
    public function isConfigured(): bool
    {
        $settings = $this->settings();

        return $settings
            && $settings->configValue('client_id')
            && $settings->configValue('api_key');
    }

    /**
     * Generate JWT authorization token.
     *
     * @return array{success: bool, token: string|null, error: string|null}
     */
    public function generateToken(): array
    {
        $settings = $this->settings();

        if (! $settings) {
            return ['success' => false, 'token' => null, 'error' => 'ClickPesa not configured'];
        }

        $cacheKey = 'clickpesa_token_' . $settings->id;

        // Cache token for 50 minutes (tokens typically expire in 60 min)
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return ['success' => true, 'token' => $cached, 'error' => null];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'client-id' => $settings->configValue('client_id'),
                    'api-key' => $settings->configValue('api_key'),
                ])
                ->post(self::BASE_URL . '/generate-token');

            if ($response->successful() && $response->json('success')) {
                $token = $response->json('token');
                Cache::put($cacheKey, $token, now()->addMinutes(50));

                return ['success' => true, 'token' => $token, 'error' => null];
            }

            Log::warning('ClickPesa token generation failed', ['response' => $response->body()]);

            return ['success' => false, 'token' => null, 'error' => 'Token generation failed: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('ClickPesa token error', ['error' => $e->getMessage()]);

            return ['success' => false, 'token' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Initiate USSD-PUSH payment request.
     *
     * @param  array{amount: string, phoneNumber: string, orderReference: string}  $data
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function initiateUssdPush(array $data): array
    {
        $tokenResult = $this->generateToken();

        if (! $tokenResult['success']) {
            return ['success' => false, 'data' => null, 'error' => $tokenResult['error']];
        }

        $payload = [
            'amount' => (string) $data['amount'],
            'currency' => 'TZS',
            'orderReference' => $data['orderReference'],
            'phoneNumber' => $data['phoneNumber'],
        ];

        // Add checksum if secret key is configured
        $checksumKey = $this->settings()?->configValue('checksum_key');
        if ($checksumKey) {
            $payload['checksum'] = $this->generateChecksum($checksumKey, $payload);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $tokenResult['token'],
                    'Content-Type' => 'application/json',
                ])
                ->post(self::BASE_URL . '/payments/initiate-ussd-push-request', $payload);

            if ($response->successful()) {
                $body = $response->json();

                Log::info('ClickPesa USSD-PUSH initiated', [
                    'orderReference' => $data['orderReference'],
                    'status' => $body['status'] ?? 'unknown',
                ]);

                return ['success' => true, 'data' => $body, 'error' => null];
            }

            Log::warning('ClickPesa USSD-PUSH failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'data' => null, 'error' => 'USSD-PUSH failed: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('ClickPesa USSD-PUSH error', ['error' => $e->getMessage()]);

            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate HMAC-SHA256 checksum for payload.
     * Recursively sorts keys and creates HMAC hash.
     */
    public function generateChecksum(string $secretKey, array $payload): string
    {
        $canonical = $this->canonicalize($payload);
        $json = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $json, $secretKey);
    }

    /**
     * Validate incoming webhook checksum.
     */
    public function validateWebhookChecksum(array $payload, string $checksum): bool
    {
        $checksumKey = $this->settings()?->configValue('checksum_key');

        if (! $checksumKey) {
            return true; // No checksum validation configured
        }

        // Remove checksum fields from payload before validation
        $data = $payload;
        unset($data['checksum'], $data['checksumMethod']);

        $expected = $this->generateChecksum($checksumKey, $data);

        return hash_equals($expected, $checksum);
    }

    /**
     * Map ClickPesa channel name to local payment_method.
     */
    public static function mapChannelToMethod(string $channel): string
    {
        return match (strtoupper($channel)) {
            'M-PESA', 'MPESA' => 'mpesa',
            'TIGO-PESA', 'TIGOPESA', 'MIXX BY YAS' => 'tigo',
            'AIRTEL-MONEY', 'AIRTELMONEY', 'AIRTEL MONEY' => 'airtel',
            'HALOPESA', 'HALO-PESA' => 'halopesa',
            default => 'mpesa',
        };
    }

    /**
     * Recursively sort array keys for canonical JSON.
     */
    private function canonicalize(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map(fn ($item) => $this->canonicalize($item), $data);
        }

        ksort($data);

        return array_map(fn ($item) => $this->canonicalize($item), $data);
    }
}
