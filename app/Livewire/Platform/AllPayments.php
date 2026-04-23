<?php

namespace App\Livewire\Platform;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('All Payments')]
class AllPayments extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $methodFilter = '';

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

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        return Payment::with(['plan', 'workspace.user'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('phone_number', 'like', "%{$this->search}%")
                    ->orWhere('transaction_id', 'like', "%{$this->search}%")
                    ->orWhereHas('workspace', fn ($wq) => $wq->where('brand_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->methodFilter, fn ($q) => $q->where('payment_method', $this->methodFilter))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function totalRevenue(): string
    {
        return number_format(Payment::completed()->sum('amount'), 0);
    }

    #[Computed]
    public function todayRevenue(): string
    {
        return number_format(Payment::completed()->whereDate('paid_at', today())->sum('amount'), 0);
    }

    #[Computed]
    public function totalCount(): int
    {
        return Payment::count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return Payment::completed()->count();
    }
}
