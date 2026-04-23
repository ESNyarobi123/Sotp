<?php

namespace App\Livewire\Platform;

use App\Models\GuestSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('All Sessions')]
class AllSessions extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sessions(): LengthAwarePaginator
    {
        return GuestSession::with(['plan', 'workspace'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('client_mac', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%")
                    ->orWhere('ssid', 'like', "%{$this->search}%")
                    ->orWhereHas('workspace', fn ($wq) => $wq->where('brand_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('time_started')
            ->paginate(25);
    }

    #[Computed]
    public function activeCount(): int
    {
        return GuestSession::active()->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return GuestSession::count();
    }

    #[Computed]
    public function todayCount(): int
    {
        return GuestSession::whereDate('created_at', today())->count();
    }
}
