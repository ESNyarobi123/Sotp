<?php

namespace App\Livewire\Customer;

use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    use UsesAuthWorkspace;

    #[Computed]
    public function workspace(): Workspace
    {
        return $this->authWorkspace();
    }

    #[Computed]
    public function onlineUsers(): int
    {
        return GuestSession::active()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function totalSessionsToday(): int
    {
        return GuestSession::where('workspace_id', $this->authWorkspace()->id)
            ->whereDate('created_at', today())
            ->count();
    }

    #[Computed]
    public function revenueToday(): string
    {
        $amount = Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereDate('paid_at', today())
            ->sum('amount');

        return number_format($amount, 0);
    }

    #[Computed]
    public function availableWalletBalance(): string
    {
        return number_format((float) $this->authWorkspace()->availableWalletBalance(), 0);
    }

    #[Computed]
    public function revenueThisMonth(): string
    {
        $amount = Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return number_format($amount, 0);
    }

    #[Computed]
    public function totalPaymentsToday(): int
    {
        return Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->whereDate('paid_at', today())
            ->count();
    }

    #[Computed]
    public function totalDevices(): int
    {
        return Device::where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function onlineDevices(): int
    {
        return Device::online()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function totalClients(): int
    {
        return GuestSession::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->distinct()
            ->count('client_mac');
    }

    #[Computed]
    public function activeClients(): int
    {
        return GuestSession::active()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->distinct()
            ->count('client_mac');
    }

    #[Computed]
    public function devicesLastSyncedAt(): ?string
    {
        return $this->authWorkspace()->devices_last_synced_at?->diffForHumans();
    }

    #[Computed]
    public function recentDevices(): EloquentCollection
    {
        return Device::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->orderByRaw("FIELD(status, 'online', 'unknown', 'offline')")
            ->orderByDesc('updated_at')
            ->take(4)
            ->get();
    }

    #[Computed]
    public function recentPayments(): EloquentCollection
    {
        return Payment::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * Recent 5 sessions for the activity feed.
     *
     * @return Collection<int, GuestSession>
     */
    #[Computed]
    public function recentSessions(): EloquentCollection
    {
        return GuestSession::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function topClients(): EloquentCollection
    {
        return GuestSession::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->select('client_mac')
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('SUM(data_used_mb) as total_data_mb')
            ->selectRaw('MAX(time_started) as last_seen')
            ->selectRaw('MAX(CASE WHEN status = "active" THEN 1 ELSE 0 END) as has_active')
            ->groupBy('client_mac', 'workspace_id')
            ->orderByDesc('last_seen')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function clickPesaSettings(): ?PaymentGatewaySetting
    {
        return PaymentGatewaySetting::query()
            ->whereNull('workspace_id')
            ->where('gateway', 'clickpesa')
            ->first();
    }
}
