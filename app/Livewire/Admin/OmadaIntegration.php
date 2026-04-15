<?php

namespace App\Livewire\Admin;

use App\Http\Middleware\DetectPublicUrl;
use App\Models\OmadaSetting;
use Flux\Flux;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Omada Integration')]
class OmadaIntegration extends Component
{
    #[Validate('required|url')]
    public string $controller_url = '';

    #[Validate('required|string|max:100')]
    public string $username = '';

    #[Validate('nullable|string|max:255')]
    public string $password = '';

    #[Validate('nullable|string|max:255')]
    public string $api_key = '';

    #[Validate('nullable|string|max:100')]
    public string $hotspot_operator_name = '';

    #[Validate('nullable|string|max:255')]
    public string $hotspot_operator_password = '';

    #[Validate('nullable|url')]
    public string $external_portal_url = '';

    #[Validate('nullable|string|max:100')]
    public string $site_id = '';

    #[Validate('nullable|string|max:100')]
    public string $omada_id = '';

    public bool $testing = false;

    /**
     * Load settings on mount.
     */
    public function mount(): void
    {
        $settings = OmadaSetting::instance();

        $this->controller_url = $settings->controller_url ?? '';
        $this->username = $settings->username ?? '';
        $this->password = '';
        $this->api_key = '';
        $this->hotspot_operator_name = $settings->hotspot_operator_name ?? '';
        $this->hotspot_operator_password = '';
        $this->external_portal_url = $settings->external_portal_url ?? '';
        $this->site_id = $settings->site_id ?? '';
        $this->omada_id = $settings->omada_id ?? '';
    }

    /**
     * Save Omada settings.
     */
    public function save(): void
    {
        $this->validate();

        $settings = OmadaSetting::instance();

        $data = [
            'controller_url' => $this->controller_url,
            'username' => $this->username,
            'hotspot_operator_name' => $this->hotspot_operator_name ?: null,
            'external_portal_url' => $this->external_portal_url ?: null,
            'site_id' => $this->site_id ?: null,
            'omada_id' => $this->omada_id ?: null,
        ];

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        if ($this->api_key !== '') {
            $data['api_key'] = $this->api_key;
        }

        if ($this->hotspot_operator_password !== '') {
            $data['hotspot_operator_password'] = $this->hotspot_operator_password;
        }

        $settings->fill($data)->save();

        $this->password = '';
        $this->api_key = '';
        $this->hotspot_operator_password = '';

        Flux::toast(variant: 'success', text: 'Omada settings saved.');
    }

    /**
     * Test connection to Omada controller.
     */
    public function testConnection(): void
    {
        $this->testing = true;
        $settings = OmadaSetting::instance();

        if (! $settings->exists || ! $settings->controller_url) {
            Flux::toast(variant: 'danger', text: 'Save settings first before testing connection.');
            $this->testing = false;

            return;
        }

        try {
            $baseUrl = rtrim($settings->controller_url, '/');
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->get("{$baseUrl}/api/info");

            if ($response->successful()) {
                $body = $response->json();
                $omadaId = $body['result']['omadacId'] ?? null;

                $settings->update([
                    'is_connected' => true,
                    'last_synced_at' => now(),
                    'omada_id' => $omadaId ?? $settings->omada_id,
                ]);

                $this->omada_id = $omadaId ?? $this->omada_id;
                Flux::toast(variant: 'success', text: 'Connected to Omada Controller successfully!');
            } else {
                $settings->update(['is_connected' => false]);
                Flux::toast(variant: 'danger', text: "Connection failed: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $settings->update(['is_connected' => false]);
            Log::error('Omada connection test failed', ['error' => $e->getMessage()]);
            Flux::toast(variant: 'danger', text: 'Connection failed: ' . $e->getMessage());
        }

        $this->testing = false;
    }

    /**
     * Auto-fill external_portal_url from the detected public URL.
     */
    public function useDetectedUrl(): void
    {
        $this->external_portal_url = DetectPublicUrl::portalUrl();
    }

    #[Computed]
    public function settings(): OmadaSetting
    {
        return OmadaSetting::instance();
    }

    #[Computed]
    public function hasCredentials(): bool
    {
        $s = $this->settings;

        return $s->exists && $s->controller_url && $s->username;
    }

    /**
     * The auto-detected public URL (from tunnel or .env).
     */
    #[Computed]
    public function detectedPublicUrl(): string
    {
        return DetectPublicUrl::portalUrl();
    }

    /**
     * The tunnel provider name, if detected.
     */
    #[Computed]
    public function tunnelProvider(): ?string
    {
        return DetectPublicUrl::tunnelProvider();
    }

    /**
     * Whether a tunnel URL is actively detected.
     */
    #[Computed]
    public function hasTunnelDetected(): bool
    {
        return DetectPublicUrl::isDetectedFromTunnel();
    }
}
