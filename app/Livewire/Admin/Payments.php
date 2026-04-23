<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\UsesAuthWorkspace;
use App\Models\Payment;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\WorkspaceWallet;
use App\Services\WalletService;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Payments')]
class Payments extends Component
{
    use UsesAuthWorkspace;
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

    #[Validate('required|numeric|min:1')]
    public string $withdrawalAmount = '';

    #[Validate('required|regex:/^0?[67][0-9]{8}$/', message: 'Enter a valid phone number (e.g. 712345678)')]
    public string $withdrawalPhoneNumber = '';

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

    public function submitWithdrawalRequest(WalletService $wallets): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $this->validateOnly('withdrawalAmount');
        $this->validateOnly('withdrawalPhoneNumber');

        $phone = '255'.ltrim($this->withdrawalPhoneNumber, '0');

        $wallets->createWithdrawalRequest(
            workspace: $this->authWorkspace(),
            requester: $user,
            amount: (float) $this->withdrawalAmount,
            phoneNumber: $phone,
        );

        $this->withdrawalAmount = '';
        $this->withdrawalPhoneNumber = '';

        unset($this->availableWalletBalance, $this->pendingWithdrawalBalance, $this->withdrawalRequests);

        Flux::toast(variant: 'success', text: 'Withdrawal request submitted and balance reserved.');
    }

    public function approveWithdrawalRequest(WalletService $wallets, int $withdrawalRequestId): void
    {
        abort_unless($this->canReviewWithdrawals(), 403);

        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $result = $wallets->approveWithdrawalRequest($withdrawalRequestId, $user);

        unset($this->withdrawalRequests, $this->pendingWithdrawalReviewQueue, $this->availableWalletBalance, $this->pendingWithdrawalBalance);

        Flux::toast(
            variant: $result['updated'] ? 'success' : 'warning',
            text: $result['updated'] ? 'Withdrawal request approved for payout review.' : 'Withdrawal request was already reviewed.',
        );
    }

    public function rejectWithdrawalRequest(WalletService $wallets, int $withdrawalRequestId): void
    {
        abort_unless($this->canReviewWithdrawals(), 403);

        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $result = $wallets->rejectWithdrawalRequest($withdrawalRequestId, $user);

        unset($this->withdrawalRequests, $this->pendingWithdrawalReviewQueue, $this->availableWalletBalance, $this->pendingWithdrawalBalance);

        Flux::toast(
            variant: $result['updated'] ? 'success' : 'warning',
            text: $result['updated'] ? 'Withdrawal request rejected and balance released.' : 'Withdrawal request was already reviewed.',
        );
    }

    public function sendWithdrawalPayout(WalletService $wallets, int $withdrawalRequestId): void
    {
        abort_unless($this->canReviewWithdrawals(), 403);

        $result = $wallets->sendWithdrawalPayout($withdrawalRequestId);

        unset(
            $this->withdrawalRequests,
            $this->pendingWithdrawalReviewQueue,
            $this->approvedWithdrawalPayoutQueue,
            $this->availableWalletBalance,
            $this->pendingWithdrawalBalance,
        );

        if (! ($result['updated'] ?? false)) {
            Flux::toast(variant: 'warning', text: $result['error'] ?? 'Withdrawal request could not be processed.');

            return;
        }

        $externalStatus = $result['external_status'] ?? null;

        Flux::toast(
            variant: match ($externalStatus) {
                'SUCCESS' => 'success',
                'AUTHORIZED' => 'warning',
                default => (($result['error'] ?? null) ? 'danger' : 'success'),
            },
            text: match ($externalStatus) {
                'SUCCESS' => 'Withdrawal payout completed successfully.',
                'AUTHORIZED' => 'Withdrawal payout was accepted by ClickPesa and is still processing.',
                default => $result['error'] ?? 'Withdrawal payout attempt recorded.',
            },
        );
    }

    public function refreshWithdrawalPayoutStatus(WalletService $wallets, int $withdrawalRequestId): void
    {
        abort_unless($this->canReviewWithdrawals(), 403);

        $result = $wallets->refreshWithdrawalPayoutStatus($withdrawalRequestId);

        unset(
            $this->withdrawalRequests,
            $this->pendingWithdrawalReviewQueue,
            $this->approvedWithdrawalPayoutQueue,
            $this->availableWalletBalance,
            $this->pendingWithdrawalBalance,
        );

        if (! ($result['updated'] ?? false) && ($result['error'] ?? null)) {
            Flux::toast(variant: 'warning', text: $result['error']);

            return;
        }

        $externalStatus = $result['external_status'] ?? null;

        Flux::toast(
            variant: match ($externalStatus) {
                'SUCCESS' => 'success',
                'PROCESSING', 'PENDING' => 'warning',
                default => (($result['error'] ?? null) ? 'danger' : 'success'),
            },
            text: match ($externalStatus) {
                'SUCCESS' => 'Withdrawal payout status refreshed and marked paid.',
                'PROCESSING' => 'Withdrawal payout is still processing at ClickPesa.',
                'PENDING' => 'Withdrawal payout is still pending at ClickPesa.',
                default => $result['error'] ?? 'Withdrawal payout status refreshed.',
            },
        );
    }

    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        return Payment::with('plan')
            ->where('workspace_id', $this->authWorkspace()->id)
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
            ? Payment::with('plan')
                ->where('workspace_id', $this->authWorkspace()->id)
                ->find($this->viewingPaymentId)
            : null;
    }

    #[Computed]
    public function totalRevenueToday(): string
    {
        return number_format(
            Payment::completed()
                ->where('workspace_id', $this->authWorkspace()->id)
                ->whereDate('paid_at', today())
                ->sum('amount'),
            0,
        );
    }

    #[Computed]
    public function canReviewWithdrawals(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    #[Computed]
    public function availableWalletBalance(): string
    {
        if (! Schema::hasTable('workspace_wallets')) {
            return number_format(0, 0);
        }

        return number_format(
            (float) (WorkspaceWallet::query()
                ->where('workspace_id', $this->authWorkspace()->id)
                ->value('available_balance') ?? 0),
            0,
        );
    }

    #[Computed]
    public function pendingWithdrawalBalance(): string
    {
        if (! Schema::hasTable('workspace_wallets')) {
            return number_format(0, 0);
        }

        return number_format(
            (float) (WorkspaceWallet::query()
                ->where('workspace_id', $this->authWorkspace()->id)
                ->value('pending_withdrawal_balance') ?? 0),
            0,
        );
    }

    #[Computed]
    public function completedCount(): int
    {
        return Payment::completed()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Payment::pending()->where('workspace_id', $this->authWorkspace()->id)->count();
    }

    #[Computed]
    public function failedCount(): int
    {
        return Payment::where('workspace_id', $this->authWorkspace()->id)->where('status', 'failed')->count();
    }

    /**
     * Revenue breakdown by payment method (last 30 days).
     *
     * @return Collection<string, float>
     */
    #[Computed]
    public function revenueByMethod(): Collection
    {
        return Payment::completed()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->where('paid_at', '>=', now()->subDays(30))
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');
    }

    #[Computed]
    public function withdrawalRequests(): Collection
    {
        return WithdrawalRequest::query()
            ->where('workspace_id', $this->authWorkspace()->id)
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function pendingWithdrawalReviewQueue(): Collection
    {
        if (! $this->canReviewWithdrawals()) {
            return collect();
        }

        return WithdrawalRequest::query()
            ->with(['workspace', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function approvedWithdrawalPayoutQueue(): Collection
    {
        if (! $this->canReviewWithdrawals()) {
            return collect();
        }

        return WithdrawalRequest::query()
            ->with(['workspace', 'requester'])
            ->whereIn('status', ['approved', 'processing', 'failed'])
            ->latest()
            ->take(10)
            ->get();
    }
}
