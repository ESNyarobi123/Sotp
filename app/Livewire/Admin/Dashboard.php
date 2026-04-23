<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    use UsesAuthWorkspace;

    /**
     * Refresh all dashboard stats when a broadcast event fires.
     */
    #[On('echo:admin-dashboard,.PaymentCompleted')]
    #[On('echo:admin-dashboard,.SessionStarted')]
    #[On('echo:admin-dashboard,.SessionDisconnected')]
    #[On('echo:admin-dashboard,.DeviceStatusChanged')]
    public function refreshFromBroadcast(): void
    {
        unset(
            $this->workspace,
            $this->onlineUsers, $this->revenueToday, $this->activeDevices,
            $this->totalDevices, $this->totalSessionsToday, $this->revenueThisWeek,
            $this->revenueThisMonth, $this->totalPaymentsToday, $this->availableWalletBalance,
            $this->recentPayments, $this->recentSessions,
        );

        $this->dispatch('charts-refresh',
            revenue: $this->revenueTrendData,
            sessions: $this->sessionsPerHourData,
        );
    }

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
    public function activeDevices(): int
    {
        return Device::online()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function totalDevices(): int
    {
        return Device::where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function totalSessionsToday(): int
    {
        return GuestSession::where('workspace_id', $this->authWorkspace()->id)->whereDate('created_at', today())->count();
    }

    #[Computed]
    public function revenueThisWeek(): string
    {
        $amount = Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->where('paid_at', '>=', now()->startOfWeek())
            ->sum('amount');

        return number_format($amount, 0);
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

    /**
     * Revenue data for the last 7 days chart.
     *
     * @return array{categories: list<string>, series: list<float>}
     */
    #[Computed]
    public function revenueTrendData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $revenue = Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->where('paid_at', '>=', now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        return [
            'categories' => $days->map(fn ($d) => Carbon::parse($d)->format('D'))->values()->toArray(),
            'series' => $days->map(fn ($d) => (float) ($revenue[$d] ?? 0))->values()->toArray(),
        ];
    }

    /**
     * Dispatch fresh chart data to Alpine.js after each poll.
     */
    public function pollDashboard(): void
    {
        $this->dispatch('charts-refresh',
            revenue: $this->revenueTrendData,
            sessions: $this->sessionsPerHourData,
        );
    }

    /**
     * Recent 10 payments for the activity feed.
     *
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function recentPayments(): Collection
    {
        return Payment::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Recent 10 sessions for the activity feed.
     *
     * @return Collection<int, GuestSession>
     */
    #[Computed]
    public function recentSessions(): Collection
    {
        return GuestSession::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Sessions per hour for the last 24 hours.
     *
     * @return array{categories: list<string>, series: list<int>}
     */
    #[Computed]
    public function sessionsPerHourData(): array
    {
        $hours = collect(range(23, 0))->map(fn ($i) => now()->subHours($i));

        $sessions = GuestSession::where('workspace_id', $this->authWorkspace()->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour');

        return [
            'categories' => $hours->map(fn ($h) => $h->format('H:00'))->values()->toArray(),
            'series' => $hours->map(fn ($h) => (int) ($sessions[$h->hour] ?? 0))->values()->toArray(),
        ];
    }

    // ── Platform-wide stats (admin only, cross-workspace) ──

    #[Computed]
    public function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    #[Computed]
    public function totalPlatformUsers(): int
    {
        return User::count();
    }

    #[Computed]
    public function totalPlatformWorkspaces(): int
    {
        return Workspace::count();
    }

    #[Computed]
    public function platformRevenueToday(): string
    {
        return number_format(Payment::completed()->whereDate('paid_at', today())->sum('amount'), 0);
    }

    #[Computed]
    public function platformRevenueMonth(): string
    {
        return number_format(Payment::completed()->where('paid_at', '>=', now()->startOfMonth())->sum('amount'), 0);
    }

    #[Computed]
    public function platformActiveSessions(): int
    {
        return GuestSession::active()->count();
    }

    #[Computed]
    public function platformOnlineDevices(): int
    {
        return Device::online()->count();
    }

    #[Computed]
    public function platformTotalDevices(): int
    {
        return Device::count();
    }

    #[Computed]
    public function suspendedWorkspaces(): int
    {
        return Workspace::where('is_suspended', true)->count();
    }

    /**
     * @return Collection<int, Workspace>
     */
    #[Computed]
    public function topWorkspaces(): Collection
    {
        return Workspace::with('user')
            ->withCount(['devices', 'guestSessions as active_sessions_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum(['payments as total_revenue' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
    }
}
