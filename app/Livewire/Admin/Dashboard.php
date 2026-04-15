<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\GuestSession;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
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
            $this->onlineUsers, $this->revenueToday, $this->activeDevices,
            $this->totalDevices, $this->totalSessionsToday, $this->revenueThisWeek,
            $this->revenueThisMonth, $this->totalPaymentsToday,
            $this->recentPayments, $this->recentSessions,
        );

        $this->dispatch('charts-refresh',
            revenue: $this->revenueTrendData,
            sessions: $this->sessionsPerHourData,
        );
    }

    #[Computed]
    public function onlineUsers(): int
    {
        return GuestSession::active()->count();
    }

    #[Computed]
    public function revenueToday(): string
    {
        $amount = Payment::completed()
            ->whereDate('paid_at', today())
            ->sum('amount');

        return number_format($amount, 0);
    }

    #[Computed]
    public function activeDevices(): int
    {
        return Device::online()->count();
    }

    #[Computed]
    public function totalDevices(): int
    {
        return Device::count();
    }

    #[Computed]
    public function totalSessionsToday(): int
    {
        return GuestSession::whereDate('created_at', today())->count();
    }

    #[Computed]
    public function revenueThisWeek(): string
    {
        $amount = Payment::completed()
            ->where('paid_at', '>=', now()->startOfWeek())
            ->sum('amount');

        return number_format($amount, 0);
    }

    #[Computed]
    public function revenueThisMonth(): string
    {
        $amount = Payment::completed()
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return number_format($amount, 0);
    }

    #[Computed]
    public function totalPaymentsToday(): int
    {
        return Payment::completed()->whereDate('paid_at', today())->count();
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Payment>
     */
    #[Computed]
    public function recentPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::with('plan')
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Recent 10 sessions for the activity feed.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GuestSession>
     */
    #[Computed]
    public function recentSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return GuestSession::with('plan')
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

        $sessions = GuestSession::where('created_at', '>=', now()->subHours(24))
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour');

        return [
            'categories' => $hours->map(fn ($h) => $h->format('H:00'))->values()->toArray(),
            'series' => $hours->map(fn ($h) => (int) ($sessions[$h->hour] ?? 0))->values()->toArray(),
        ];
    }
}
