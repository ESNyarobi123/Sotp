<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\OmadaSetting;
use App\Services\OmadaService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Devices (APs)')]
class Devices extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public bool $showForm = false;

    public bool $showGuide = false;

    public bool $syncing = false;

    public ?int $editingDeviceId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/')]
    public string $ap_mac = '';

    #[Validate('nullable|string|max:100')]
    public string $site_name = '';

    #[Validate('nullable|ip')]
    public string $ip_address = '';

    #[Validate('nullable|string|max:50')]
    public string $model = '';

    /**
     * Sync devices from Omada controller.
     */
    public function syncFromOmada(): void
    {
        $this->syncing = true;

        $result = app(OmadaService::class)->syncDevicesFromOmada();

        if ($result['success']) {
            OmadaSetting::instance()->touch('last_synced_at');
            Flux::toast(variant: 'success', text: "Synced {$result['synced']} device(s) from Omada.");
        } else {
            Flux::toast(variant: 'danger', text: 'Sync failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        unset($this->devices, $this->onlineCount, $this->offlineCount, $this->totalCount, $this->lastSyncedAt, $this->totalClients);
        $this->syncing = false;
    }

    /**
     * Rename device locally and push to Omada controller.
     */
    public function renameOnOmada(int $deviceId, string $newName): void
    {
        $device = Device::findOrFail($deviceId);

        if ($device->omada_device_id || $device->status !== 'unknown') {
            $result = app(OmadaService::class)->renameDevice($device->ap_mac, $newName);

            if ($result['success']) {
                $device->update(['name' => $newName]);
                Flux::toast(variant: 'success', text: "Renamed to '{$newName}' on Omada.");
            } else {
                $device->update(['name' => $newName]);
                Flux::toast(variant: 'warning', text: "Saved locally. Omada push failed: " . ($result['error'] ?? 'Unknown'));
            }
        } else {
            $device->update(['name' => $newName]);
            Flux::toast(variant: 'success', text: 'Device renamed locally.');
        }

        unset($this->devices);
    }

    /**
     * Reboot a device via Omada controller.
     */
    public function rebootDevice(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);

        $result = app(OmadaService::class)->rebootDevice($device->ap_mac);

        if ($result['success']) {
            Flux::toast(variant: 'success', text: "Reboot initiated for '{$device->name}'.");
        } else {
            Flux::toast(variant: 'danger', text: 'Reboot failed: ' . ($result['error'] ?? 'Unknown'));
        }
    }

    #[Computed]
    public function lastSyncedAt(): ?string
    {
        $setting = OmadaSetting::instance();

        return $setting->last_synced_at?->diffForHumans();
    }

    #[Computed]
    public function totalClients(): int
    {
        return Device::online()->sum('clients_count');
    }

    /**
     * Open create form.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open edit form.
     */
    public function edit(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);

        $this->editingDeviceId = $device->id;
        $this->name = $device->name;
        $this->ap_mac = $device->ap_mac;
        $this->site_name = $device->site_name ?? '';
        $this->ip_address = $device->ip_address ?? '';
        $this->model = $device->model ?? '';
        $this->showForm = true;
    }

    /**
     * Save or update device (with optional push to Omada).
     */
    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'ap_mac' => strtoupper($validated['ap_mac']),
            'site_name' => $validated['site_name'] ?: null,
            'ip_address' => $validated['ip_address'] ?: null,
            'model' => $validated['model'] ?: null,
        ];

        if ($this->editingDeviceId) {
            $device = Device::findOrFail($this->editingDeviceId);
            $oldName = $device->name;
            $device->update($data);

            // Push name change to Omada if it changed
            if ($oldName !== $data['name'] && $device->omada_device_id) {
                app(OmadaService::class)->renameDevice($device->ap_mac, $data['name']);
            }

            Flux::toast(variant: 'success', text: 'Device updated.');
        } else {
            $data['status'] = 'unknown';
            Device::create($data);
            Flux::toast(variant: 'success', text: 'Device added.');
        }

        $this->closeForm();
    }

    /**
     * Delete a device.
     */
    public function delete(int $deviceId): void
    {
        Device::findOrFail($deviceId)->delete();
        Flux::toast(variant: 'success', text: 'Device removed.');
    }

    /**
     * Close form and reset.
     */
    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    #[Computed]
    public function devices(): \Illuminate\Database\Eloquent\Collection
    {
        return Device::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('ap_mac', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%")
                    ->orWhere('site_name', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("FIELD(status, 'online', 'unknown', 'offline')")
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function onlineCount(): int
    {
        return Device::online()->count();
    }

    #[Computed]
    public function offlineCount(): int
    {
        return Device::where('status', 'offline')->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Device::count();
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->editingDeviceId = null;
        $this->name = '';
        $this->ap_mac = '';
        $this->site_name = '';
        $this->ip_address = '';
        $this->model = '';
        $this->resetValidation();
    }
}
