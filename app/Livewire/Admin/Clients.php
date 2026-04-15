<?php

namespace App\Livewire\Admin;

use App\Models\GuestSession;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Clients')]
class Clients extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?string $viewingMac = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewClient(string $mac): void
    {
        $this->viewingMac = $mac;
    }

    public function closeDetail(): void
    {
        $this->viewingMac = null;
    }

    /**
     * Paginated list of unique clients aggregated from guest_sessions.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    #[Computed]
    public function clients(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return GuestSession::query()
            ->select('client_mac')
            ->selectRaw('COUNT(*) as total_sessions')
            ->selectRaw('SUM(data_used_mb) as total_data_mb')
            ->selectRaw('MAX(time_started) as last_seen')
            ->selectRaw('MAX(CASE WHEN status = "active" THEN 1 ELSE 0 END) as has_active')
            ->selectRaw('(SELECT SUM(p.amount) FROM payments p WHERE p.client_mac = guest_sessions.client_mac AND p.status = "completed") as total_spent')
            ->groupBy('client_mac')
            ->when($this->search, fn ($q) => $q->where('client_mac', 'like', "%{$this->search}%"))
            ->when($this->statusFilter === 'active', fn ($q) => $q->havingRaw('MAX(CASE WHEN status = "active" THEN 1 ELSE 0 END) = 1'))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->havingRaw('MAX(CASE WHEN status = "active" THEN 1 ELSE 0 END) = 0'))
            ->orderByDesc('last_seen')
            ->paginate(25);
    }

    /**
     * All sessions for the client being viewed.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, GuestSession>|null
     */
    #[Computed]
    public function clientSessions(): ?\Illuminate\Database\Eloquent\Collection
    {
        if (! $this->viewingMac) {
            return null;
        }

        return GuestSession::with('plan')
            ->where('client_mac', $this->viewingMac)
            ->latest('time_started')
            ->take(10)
            ->get();
    }

    /**
     * Recent payments for the client being viewed.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Payment>|null
     */
    #[Computed]
    public function clientPayments(): ?\Illuminate\Database\Eloquent\Collection
    {
        if (! $this->viewingMac) {
            return null;
        }

        return Payment::with('plan')
            ->where('client_mac', $this->viewingMac)
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function totalClients(): int
    {
        return GuestSession::distinct('client_mac')->count('client_mac');
    }

    #[Computed]
    public function activeClients(): int
    {
        return GuestSession::active()
            ->distinct('client_mac')
            ->count('client_mac');
    }

    #[Computed]
    public function totalRevenueFromClients(): string
    {
        return number_format(
            Payment::completed()->sum('amount'),
            0
        );
    }
}
