<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Payments')]
class Payments extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $methodFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $viewingPaymentId = null;

    /**
     * Reset pagination when filters change.
     */
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

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Open detail modal.
     */
    public function viewPayment(int $paymentId): void
    {
        $this->viewingPaymentId = $paymentId;
    }

    /**
     * Close detail modal.
     */
    public function closePaymentDetail(): void
    {
        $this->viewingPaymentId = null;
    }

    #[Computed]
    public function payments(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Payment::with('plan')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('phone_number', 'like', "%{$this->search}%")
                    ->orWhere('transaction_id', 'like', "%{$this->search}%")
                    ->orWhere('mpesa_receipt_number', 'like', "%{$this->search}%")
                    ->orWhere('client_mac', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->methodFilter, fn ($q) => $q->where('payment_method', $this->methodFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('paid_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function viewingPayment(): ?Payment
    {
        return $this->viewingPaymentId
            ? Payment::with('plan')->find($this->viewingPaymentId)
            : null;
    }

    #[Computed]
    public function totalRevenueToday(): string
    {
        return number_format(
            Payment::completed()->whereDate('paid_at', today())->sum('amount'),
            0,
        );
    }

    #[Computed]
    public function completedCount(): int
    {
        return Payment::completed()->count();
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Payment::pending()->count();
    }

    #[Computed]
    public function failedCount(): int
    {
        return Payment::where('status', 'failed')->count();
    }

    /**
     * Revenue breakdown by payment method (last 30 days).
     *
     * @return \Illuminate\Support\Collection<string, float>
     */
    #[Computed]
    public function revenueByMethod(): \Illuminate\Support\Collection
    {
        return Payment::completed()
            ->where('paid_at', '>=', now()->subDays(30))
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');
    }
}
