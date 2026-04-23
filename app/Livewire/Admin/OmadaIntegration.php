<?php

namespace App\Livewire\Admin;

use App\Http\Middleware\DetectPublicUrl;
use App\Jobs\ProvisionWorkspaceOmadaSiteJob;
use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\OmadaSetting;
use App\Models\Workspace;
use App\Services\OmadaService;
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
    use UsesAuthWorkspace;

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

    public string $adoptDeviceMac = '';

    public string $adoptDeviceUsername = '';

    public string $adoptDevicePassword = '';

    public array $adoptDeviceResult = [];

    public bool $testing = false;

    /**
     * Load settings on mount.
     */
    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);

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
            Flux::toast(variant: 'danger', text: 'Connection failed: '.$e->getMessage());
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

    public function retryProvisioning(): void
    {
        $workspace = $this->authWorkspace();

        if ($workspace->isOmadaReady()) {
            Flux::toast(variant: 'success', text: 'This workspace is already ready on Omada.');

            return;
        }

        if (! app(OmadaService::class)->isConfigured()) {
            Flux::toast(variant: 'danger', text: 'Open API automation is not fully configured yet. Complete the readiness checklist first.');

            return;
        }

        $workspace->forceFill([
            'provisioning_status' => 'pending',
            'provisioning_error' => null,
        ])->save();

        ProvisionWorkspaceOmadaSiteJob::dispatch($workspace);

        unset($this->workspace);

        Flux::toast(variant: 'success', text: 'Workspace provisioning has been queued again.');
    }

    public function refreshPendingDeviceInventory(): void
    {
        app(OmadaService::class)->forgetPendingDeviceInventory($this->workspace);

        unset($this->pendingDeviceInventory);

        Flux::toast(variant: 'success', text: 'Pending device inventory refreshed.');
    }

    public function selectPendingDeviceForAdoption(string $deviceMac): void
    {
        $this->adoptDeviceMac = $deviceMac;
        $this->adoptDeviceResult = [];
    }

    public function startDeviceAdoption(): void
    {
        $this->validate([
            'adoptDeviceMac' => 'required|string|max:32',
            'adoptDeviceUsername' => 'required|string|max:64',
            'adoptDevicePassword' => 'required|string|max:64',
        ]);

        $result = app(OmadaService::class)->startAdoptDevice(
            $this->adoptDeviceMac,
            $this->adoptDeviceUsername,
            $this->adoptDevicePassword,
            $this->workspace,
        );

        if (! $result['success']) {
            $this->adoptDeviceResult = [
                'status' => 'error',
                'title' => 'Adopt request failed',
                'message' => $result['error'] ?? 'Unable to start device adoption.',
                'device_mac' => $this->adoptDeviceMac,
            ];

            Flux::toast(variant: 'danger', text: 'Adopt request failed: '.($result['error'] ?? 'Unknown error'));

            return;
        }

        app(OmadaService::class)->forgetPendingDeviceInventory($this->workspace);
        unset($this->pendingDeviceInventory);

        $this->adoptDeviceResult = [
            'status' => 'pending',
            'title' => 'Adopt request sent',
            'message' => 'Omada accepted the adopt request. Click Check adopt result to confirm whether the device joined the site successfully.',
            'device_mac' => $this->adoptDeviceMac,
        ];

        Flux::toast(variant: 'success', text: 'Adopt request sent to Omada.');
    }

    public function checkAdoptDeviceResult(): void
    {
        $this->validate([
            'adoptDeviceMac' => 'required|string|max:32',
        ]);

        $result = app(OmadaService::class)->getAdoptDeviceResult($this->adoptDeviceMac, $this->workspace);

        if ($result['adopted']) {
            app(OmadaService::class)->forgetPendingDeviceInventory($this->workspace);
            unset($this->pendingDeviceInventory);

            $this->adoptDeviceResult = [
                'status' => 'success',
                'title' => 'Device adopted successfully',
                'message' => 'The device reported a successful adoption result. You can now sync from Omada to import it into SKY.',
                'device_mac' => $result['device_mac'] ?? $this->adoptDeviceMac,
            ];

            Flux::toast(variant: 'success', text: 'Device adoption completed successfully.');

            return;
        }

        $this->adoptDeviceResult = [
            'status' => 'error',
            'title' => 'Adopt result needs attention',
            'message' => $result['error'] ?? 'Device adoption is not complete yet.',
            'device_mac' => $result['device_mac'] ?? $this->adoptDeviceMac,
            'adopt_error_code' => $result['adopt_error_code'] ?? null,
            'adopt_failed_type' => $result['adopt_failed_type'] ?? null,
        ];

        Flux::toast(variant: 'danger', text: 'Adopt result indicates the device still needs attention.');
    }

    #[Computed]
    public function settings(): OmadaSetting
    {
        return OmadaSetting::instance();
    }

    #[Computed]
    public function workspace(): Workspace
    {
        return $this->authWorkspace();
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

    #[Computed]
    public function auditCapabilities(): array
    {
        return app(OmadaService::class)->auditCapabilities();
    }

    #[Computed]
    public function auditNotes(): array
    {
        return app(OmadaService::class)->auditNotes();
    }

    #[Computed]
    public function automationReadiness(): array
    {
        return app(OmadaService::class)->automationReadiness($this->external_portal_url);
    }

    #[Computed]
    public function finalizeSiteReadiness(): array
    {
        return app(OmadaService::class)->finalizeSiteReadiness($this->workspace, $this->external_portal_url);
    }

    #[Computed]
    public function deviceAdoptionStatus(): array
    {
        return app(OmadaService::class)->deviceAdoptionStatus($this->workspace);
    }

    #[Computed]
    public function pendingDeviceInventory(): array
    {
        return app(OmadaService::class)->pendingDeviceInventory($this->workspace);
    }
}
