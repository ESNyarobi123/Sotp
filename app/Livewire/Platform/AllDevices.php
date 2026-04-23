<?php

namespace App\Livewire\Platform;

use App\Models\Device;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('All Devices')]
class AllDevices extends Component
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
    public function devices(): LengthAwarePaginator
    {
        return Device::with('workspace')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('ap_mac', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%")
                    ->orWhereHas('workspace', fn ($wq) => $wq->where('brand_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("FIELD(status, 'online', 'unknown', 'offline')")
            ->orderBy('name')
            ->paginate(25);
    }

    #[Computed]
    public function onlineCount(): int
    {
        return Device::online()->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Device::count();
    }

    #[Computed]
    public function totalClients(): int
    {
        return Device::online()->sum('clients_count');
    }
}
