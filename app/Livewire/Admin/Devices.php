<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\Device;
use App\Models\Workspace;
use App\Services\OmadaService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Devices (APs)')]
class Devices extends Component
{
    use UsesAuthWorkspace;

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

    public string $adoptDeviceMac = '';

    public string $adoptDeviceUsername = '';

    public string $adoptDevicePassword = '';

    public array $adoptDeviceResult = [];

    /**
     * Sync devices from Omada controller.
     */
    public function syncFromOmada(): void
    {
        $this->syncing = true;

        $workspace = $this->workspace;

        if (! $workspace->isOmadaReady()) {
            $summary = $workspace->provisioningSummary();
            Flux::toast(variant: 'warning', text: $summary['title'].': '.$summary['message']);
            $this->syncing = false;

            return;
        }

        $result = app(OmadaService::class)->syncDevicesFromOmada($workspace);

        if ($result['success']) {
            $workspace->forceFill(['devices_last_synced_at' => now()])->save();
            Flux::toast(variant: 'success', text: "Synced {$result['synced']} device(s) from Omada.");
        } else {
            Flux::toast(variant: 'danger', text: 'Sync failed: '.($result['error'] ?? 'Unknown error'));
        }

        unset($this->devices, $this->onlineCount, $this->offlineCount, $this->totalCount, $this->lastSyncedAt, $this->totalClients);
        $this->syncing = false;
    }

    /**
     * Rename device locally and push to Omada controller.
     */
    public function renameOnOmada(int $deviceId, string $newName): void
    {
        $device = Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereKey($deviceId)
            ->firstOrFail();

        if ($device->omada_device_id || $device->status !== 'unknown') {
            $result = app(OmadaService::class)->renameDevice($device->ap_mac, $newName, $this->authWorkspace());

            if ($result['success']) {
                $device->update(['name' => $newName]);
                Flux::toast(variant: 'success', text: "Renamed to '{$newName}' on Omada.");
            } else {
                $device->update(['name' => $newName]);
                Flux::toast(variant: 'warning', text: 'Saved locally. Omada push failed: '.($result['error'] ?? 'Unknown'));
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
        $device = Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereKey($deviceId)
            ->firstOrFail();

        $result = app(OmadaService::class)->rebootDevice($device->ap_mac, $this->authWorkspace());

        if ($result['success']) {
            Flux::toast(variant: 'success', text: "Reboot initiated for '{$device->name}'.");
        } else {
            Flux::toast(variant: 'danger', text: 'Reboot failed: '.($result['error'] ?? 'Unknown'));
        }
    }

    public function refreshPendingDeviceInventory(): void
    {
        app(OmadaService::class)->forgetPendingDeviceInventory($this->workspace);

        unset($this->pendingDeviceInventory);

        Flux::toast(variant: 'success', text: 'Pending device inventory refreshed.');
    }

    public function selectPendingDeviceForAdoption(string $deviceMac): void
    {
        abort_unless($this->canTriggerDeviceAdoption, 403);

        $this->adoptDeviceMac = $deviceMac;
        $this->adoptDeviceResult = [];
    }

    public function startDeviceAdoption(): void
    {
        abort_unless($this->canTriggerDeviceAdoption, 403);

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
        abort_unless($this->canTriggerDeviceAdoption, 403);

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
    public function lastSyncedAt(): ?string
    {
        return $this->authWorkspace()->devices_last_synced_at?->diffForHumans();
    }

    #[Computed]
    public function workspace(): Workspace
    {
        return $this->authWorkspace();
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

    #[Computed]
    public function canTriggerDeviceAdoption(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    #[Computed]
    public function totalClients(): int
    {
        return Device::online()->where('workspace_id', $this->authWorkspace()->id)->sum('clients_count');
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
        $device = Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereKey($deviceId)
            ->firstOrFail();

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
            $device = Device::query()
                ->where('workspace_id', $this->authWorkspace()->id)
                ->whereKey($this->editingDeviceId)
                ->firstOrFail();
            $oldName = $device->name;
            $device->update($data);

            // Push name change to Omada if it changed
            if ($oldName !== $data['name'] && $device->omada_device_id) {
                app(OmadaService::class)->renameDevice($device->ap_mac, $data['name'], $this->authWorkspace());
            }

            Flux::toast(variant: 'success', text: 'Device updated.');
        } else {
            $workspace = $this->authWorkspace();
            $currentCount = Device::where('workspace_id', $workspace->id)->count();

            if ($workspace->max_devices > 0 && $currentCount >= $workspace->max_devices) {
                Flux::toast(variant: 'danger', text: "Device limit reached ({$workspace->max_devices}). Contact your admin to increase the limit.");

                return;
            }

            $data['status'] = 'unknown';
            $data['workspace_id'] = $workspace->id;
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
        Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereKey($deviceId)
            ->firstOrFail()
            ->delete();
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
    public function devices(): Collection
    {
        return Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
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
        return Device::online()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function offlineCount(): int
    {
        return Device::where('workspace_id', $this->authWorkspace()->id)->where('status', 'offline')->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Device::where('workspace_id', $this->authWorkspace()->id)->count();
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
