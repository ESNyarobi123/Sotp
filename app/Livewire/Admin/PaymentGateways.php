<?php

namespace App\Livewire\Admin;

use App\Models\PaymentGatewaySetting;
use App\Services\ClickPesaService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Payment Gateways')]
class PaymentGateways extends Component
{
    public bool $showClickPesaForm = false;

    #[Validate('required|string|max:100')]
    public string $client_id = '';

    #[Validate('nullable|string|max:255')]
    public string $api_key = '';

    #[Validate('nullable|string|max:255')]
    public string $checksum_key = '';

    #[Validate('nullable|url')]
    public string $webhook_url = '';

    public bool $is_active = true;

    /**
     * Load existing ClickPesa settings on mount.
     */
    public function mount(): void
    {
        $settings = PaymentGatewaySetting::where('gateway', 'clickpesa')->first();

        if ($settings) {
            $this->client_id = $settings->configValue('client_id', '');
            $this->api_key = '';
            $this->checksum_key = '';
            $this->webhook_url = $settings->configValue('webhook_url', '');
            $this->is_active = $settings->is_active;
        }
    }

    /**
     * Open ClickPesa settings form.
     */
    public function editClickPesa(): void
    {
        $this->showClickPesaForm = true;
    }

    /**
     * Save ClickPesa gateway settings.
     */
    public function saveClickPesa(): void
    {
        $this->validate();

        $settings = PaymentGatewaySetting::firstOrNew(['gateway' => 'clickpesa']);

        $config = $settings->config ?? [];
        $config['client_id'] = $this->client_id;
        $config['webhook_url'] = $this->webhook_url ?: url('/api/clickpesa/webhook');

        if ($this->api_key !== '') {
            $config['api_key'] = $this->api_key;
        }

        if ($this->checksum_key !== '') {
            $config['checksum_key'] = $this->checksum_key;
        }

        $settings->fill([
            'display_name' => 'ClickPesa',
            'is_active' => $this->is_active,
            'config' => $config,
        ])->save();

        $this->api_key = '';
        $this->checksum_key = '';
        $this->showClickPesaForm = false;

        Flux::toast(variant: 'success', text: 'ClickPesa settings saved.');
    }

    /**
     * Test ClickPesa API connection.
     */
    public function testClickPesa(): void
    {
        $service = app(ClickPesaService::class);

        if (! $service->isConfigured()) {
            Flux::toast(variant: 'danger', text: 'Save ClickPesa credentials first.');

            return;
        }

        $result = $service->generateToken();

        if ($result['success']) {
            Flux::toast(variant: 'success', text: 'ClickPesa API connected! Token generated successfully.');
        } else {
            Flux::toast(variant: 'danger', text: 'Connection failed: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    /**
     * Close form and clear validation errors.
     */
    public function closeClickPesaForm(): void
    {
        $this->showClickPesaForm = false;
        $this->resetValidation();
    }

    #[Computed]
    public function clickPesaSettings(): ?PaymentGatewaySetting
    {
        return PaymentGatewaySetting::where('gateway', 'clickpesa')->first();
    }

}
