<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Models\Workspace;
use App\Models\WorkspaceWallet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function ensureWorkspaceWallet(Workspace $workspace, string $currency = 'TZS'): WorkspaceWallet
    {
        return WorkspaceWallet::firstOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'currency' => $currency,
                'available_balance' => 0,
                'pending_withdrawal_balance' => 0,
                'lifetime_credited' => 0,
                'lifetime_withdrawn' => 0,
            ],
        );
    }

    public function creditCompletedPayment(Payment $payment): array
    {
        if (! $payment->isCompleted() || $payment->workspace_id === null) {
            return ['credited' => false, 'wallet' => null, 'transaction' => null];
        }

        return DB::transaction(function () use ($payment): array {
            $existing = WalletTransaction::query()
                ->where('type', 'payment_credit')
                ->where('reference_type', Payment::class)
                ->where('reference_id', $payment->id)
                ->first();

            if ($existing) {
                return [
                    'credited' => false,
                    'wallet' => $existing->wallet,
                    'transaction' => $existing,
                ];
            }

            $this->ensureWorkspaceWallet($payment->workspace, $payment->currency);

            $wallet = WorkspaceWallet::query()
                ->where('workspace_id', $payment->workspace_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $wallet->available_balance;
            $amount = (float) $payment->amount;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->forceFill([
                'currency' => $payment->currency,
                'available_balance' => $balanceAfter,
                'lifetime_credited' => (float) $wallet->lifetime_credited + $amount,
            ])->save();

            $transaction = WalletTransaction::create([
                'workspace_wallet_id' => $wallet->id,
                'workspace_id' => $payment->workspace_id,
                'type' => 'payment_credit',
                'amount' => $amount,
                'currency' => $payment->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'external_reference' => $payment->transaction_id,
                'description' => 'ClickPesa payment credit',
                'meta' => [
                    'payment_status' => $payment->status,
                    'clickpesa_order_id' => $payment->clickpesa_order_id,
                    'clickpesa_payment_reference' => $payment->clickpesa_payment_reference,
                ],
            ]);

            return ['credited' => true, 'wallet' => $wallet->fresh(), 'transaction' => $transaction];
        });
    }

    public function createWithdrawalRequest(Workspace $workspace, User $requester, float $amount, string $phoneNumber): array
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'withdrawalAmount' => 'Enter a withdrawal amount greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($workspace, $requester, $amount, $phoneNumber): array {
            $this->ensureWorkspaceWallet($workspace);

            $wallet = WorkspaceWallet::query()
                ->where('workspace_id', $workspace->id)
                ->lockForUpdate()
                ->firstOrFail();

            $availableBalance = (float) $wallet->available_balance;

            if ($amount > $availableBalance) {
                throw ValidationException::withMessages([
                    'withdrawalAmount' => 'Withdrawal amount exceeds your available balance.',
                ]);
            }

            $balanceAfter = $availableBalance - $amount;
            $pendingAfter = (float) $wallet->pending_withdrawal_balance + $amount;

            $wallet->forceFill([
                'available_balance' => $balanceAfter,
                'pending_withdrawal_balance' => $pendingAfter,
            ])->save();

            $reference = 'WDR'.Str::upper(Str::random(10));

            $withdrawalRequest = WithdrawalRequest::create([
                'workspace_id' => $workspace->id,
                'workspace_wallet_id' => $wallet->id,
                'requested_by' => $requester->id,
                'reference' => $reference,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'phone_number' => $phoneNumber,
                'meta' => [
                    'available_balance_before' => $availableBalance,
                    'available_balance_after' => $balanceAfter,
                ],
            ]);

            $transaction = WalletTransaction::create([
                'workspace_wallet_id' => $wallet->id,
                'workspace_id' => $workspace->id,
                'type' => 'withdrawal_hold',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'balance_before' => $availableBalance,
                'balance_after' => $balanceAfter,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $withdrawalRequest->id,
                'external_reference' => $reference,
                'description' => 'Withdrawal request hold',
                'meta' => [
                    'phone_number' => $phoneNumber,
                    'status' => 'pending',
                ],
            ]);

            return [
                'request' => $withdrawalRequest,
                'wallet' => $wallet->fresh(),
                'transaction' => $transaction,
            ];
        });
    }

    public function approveWithdrawalRequest(int $withdrawalRequestId, User $reviewer): array
    {
        return DB::transaction(function () use ($withdrawalRequestId, $reviewer): array {
            $withdrawalRequest = WithdrawalRequest::query()
                ->whereKey($withdrawalRequestId)
                ->lockForUpdate()
                ->first();

            if (! $withdrawalRequest instanceof WithdrawalRequest) {
                throw (new ModelNotFoundException)->setModel(WithdrawalRequest::class, [$withdrawalRequestId]);
            }

            if ($withdrawalRequest->status !== 'pending') {
                return ['updated' => false, 'request' => $withdrawalRequest];
            }

            $withdrawalRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'approved_at' => now(),
                'rejected_at' => null,
                'failure_reason' => null,
            ])->save();

            return ['updated' => true, 'request' => $withdrawalRequest->fresh()];
        });
    }

    public function rejectWithdrawalRequest(int $withdrawalRequestId, User $reviewer): array
    {
        return DB::transaction(function () use ($withdrawalRequestId, $reviewer): array {
            $withdrawalRequest = WithdrawalRequest::query()
                ->whereKey($withdrawalRequestId)
                ->lockForUpdate()
                ->first();

            if (! $withdrawalRequest instanceof WithdrawalRequest) {
                throw (new ModelNotFoundException)->setModel(WithdrawalRequest::class, [$withdrawalRequestId]);
            }

            if ($withdrawalRequest->status !== 'pending') {
                return ['updated' => false, 'request' => $withdrawalRequest];
            }

            $wallet = WorkspaceWallet::query()
                ->whereKey($withdrawalRequest->workspace_wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $withdrawalRequest->amount;
            $availableBalanceBefore = (float) $wallet->available_balance;
            $availableBalanceAfter = $availableBalanceBefore + $amount;
            $pendingAfter = max(0, (float) $wallet->pending_withdrawal_balance - $amount);

            $wallet->forceFill([
                'available_balance' => $availableBalanceAfter,
                'pending_withdrawal_balance' => $pendingAfter,
            ])->save();

            $withdrawalRequest->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'approved_at' => null,
                'rejected_at' => now(),
                'failure_reason' => 'Rejected by admin review.',
            ])->save();

            $transaction = WalletTransaction::create([
                'workspace_wallet_id' => $wallet->id,
                'workspace_id' => $withdrawalRequest->workspace_id,
                'type' => 'withdrawal_rejected',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'balance_before' => $availableBalanceBefore,
                'balance_after' => $availableBalanceAfter,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $withdrawalRequest->id,
                'external_reference' => $withdrawalRequest->reference,
                'description' => 'Withdrawal request rejected and balance released',
                'meta' => [
                    'status' => 'rejected',
                    'reviewed_by' => $reviewer->id,
                ],
            ]);

            return [
                'updated' => true,
                'request' => $withdrawalRequest->fresh(),
                'wallet' => $wallet->fresh(),
                'transaction' => $transaction,
            ];
        });
    }

    public function sendWithdrawalPayout(int $withdrawalRequestId): array
    {
        $withdrawalRequest = WithdrawalRequest::query()
            ->with('workspace')
            ->findOrFail($withdrawalRequestId);

        if (! in_array($withdrawalRequest->status, ['approved', 'failed'], true)) {
            return [
                'updated' => false,
                'request' => $withdrawalRequest,
                'error' => 'Withdrawal request is not ready for payout.',
            ];
        }

        $result = app(ClickPesaService::class)
            ->forWorkspace($withdrawalRequest->workspace)
            ->createMobileMoneyPayout([
                'amount' => (string) (float) $withdrawalRequest->amount,
                'phoneNumber' => $withdrawalRequest->phone_number,
                'currency' => $withdrawalRequest->currency,
                'orderReference' => $withdrawalRequest->reference,
            ]);

        return DB::transaction(function () use ($withdrawalRequestId, $result): array {
            $withdrawalRequest = WithdrawalRequest::query()
                ->whereKey($withdrawalRequestId)
                ->lockForUpdate()
                ->first();

            if (! $withdrawalRequest instanceof WithdrawalRequest) {
                throw (new ModelNotFoundException)->setModel(WithdrawalRequest::class, [$withdrawalRequestId]);
            }

            if (! in_array($withdrawalRequest->status, ['approved', 'failed'], true)) {
                return [
                    'updated' => false,
                    'request' => $withdrawalRequest,
                    'error' => 'Withdrawal request is no longer ready for payout.',
                ];
            }

            $meta = array_merge($withdrawalRequest->meta ?? [], [
                'payout_last_attempted_at' => now()->toIso8601String(),
            ]);

            if (! $result['success']) {
                $withdrawalRequest->forceFill([
                    'status' => 'failed',
                    'failure_reason' => $result['error'],
                    'paid_at' => null,
                    'meta' => $meta,
                ])->save();

                return [
                    'updated' => true,
                    'request' => $withdrawalRequest->fresh(),
                    'error' => $result['error'],
                ];
            }

            $payload = $result['data'] ?? [];
            $externalStatus = strtoupper((string) ($payload['status'] ?? ''));

            $meta = array_merge($meta, [
                'payout_id' => $payload['id'] ?? null,
                'payout_status' => $externalStatus,
                'payout_channel' => $payload['channel'] ?? null,
                'payout_channel_provider' => $payload['channelProvider'] ?? null,
                'payout_fee' => $payload['fee'] ?? null,
                'payout_beneficiary' => $payload['beneficiary'] ?? null,
            ]);

            if ($externalStatus === 'SUCCESS') {
                return $this->completeWithdrawalPayout(
                    withdrawalRequest: $withdrawalRequest,
                    payload: $payload,
                    meta: $meta,
                    externalStatus: $externalStatus,
                );
            }

            if ($externalStatus === 'AUTHORIZED') {
                $withdrawalRequest->forceFill([
                    'status' => 'processing',
                    'paid_at' => null,
                    'failure_reason' => null,
                    'meta' => $meta,
                ])->save();

                return [
                    'updated' => true,
                    'request' => $withdrawalRequest->fresh(),
                    'external_status' => $externalStatus,
                ];
            }

            $withdrawalRequest->forceFill([
                'status' => 'failed',
                'paid_at' => null,
                'failure_reason' => $externalStatus !== '' ? 'ClickPesa payout returned '.$externalStatus.'.' : 'ClickPesa payout did not return a successful status.',
                'meta' => $meta,
            ])->save();

            return [
                'updated' => true,
                'request' => $withdrawalRequest->fresh(),
                'external_status' => $externalStatus,
                'error' => $withdrawalRequest->failure_reason,
            ];
        });
    }

    public function refreshWithdrawalPayoutStatus(int $withdrawalRequestId): array
    {
        $withdrawalRequest = WithdrawalRequest::query()
            ->with('workspace')
            ->findOrFail($withdrawalRequestId);

        if ($withdrawalRequest->status !== 'processing') {
            return [
                'updated' => false,
                'request' => $withdrawalRequest,
                'error' => 'Withdrawal request is not currently processing.',
            ];
        }

        $result = app(ClickPesaService::class)
            ->forWorkspace($withdrawalRequest->workspace)
            ->queryPayoutStatus($withdrawalRequest->reference);

        return DB::transaction(function () use ($withdrawalRequestId, $result): array {
            $withdrawalRequest = WithdrawalRequest::query()
                ->whereKey($withdrawalRequestId)
                ->lockForUpdate()
                ->first();

            if (! $withdrawalRequest instanceof WithdrawalRequest) {
                throw (new ModelNotFoundException)->setModel(WithdrawalRequest::class, [$withdrawalRequestId]);
            }

            if ($withdrawalRequest->status !== 'processing') {
                return [
                    'updated' => false,
                    'request' => $withdrawalRequest,
                    'error' => 'Withdrawal request is no longer processing.',
                ];
            }

            $meta = array_merge($withdrawalRequest->meta ?? [], [
                'payout_last_checked_at' => now()->toIso8601String(),
            ]);

            if (! $result['success']) {
                $withdrawalRequest->forceFill([
                    'meta' => array_merge($meta, ['payout_last_check_error' => $result['error']]),
                ])->save();

                return [
                    'updated' => false,
                    'request' => $withdrawalRequest->fresh(),
                    'error' => $result['error'],
                ];
            }

            $payload = $result['data'] ?? [];
            $externalStatus = strtoupper((string) ($payload['status'] ?? ''));

            $meta = array_merge($meta, [
                'payout_last_check_error' => null,
                'payout_id' => $payload['id'] ?? data_get($withdrawalRequest->meta, 'payout_id'),
                'payout_status' => $externalStatus,
                'payout_channel' => $payload['channel'] ?? data_get($withdrawalRequest->meta, 'payout_channel'),
                'payout_channel_provider' => $payload['channelProvider'] ?? data_get($withdrawalRequest->meta, 'payout_channel_provider'),
                'payout_fee' => $payload['fee'] ?? data_get($withdrawalRequest->meta, 'payout_fee'),
                'payout_beneficiary' => $payload['beneficiary'] ?? data_get($withdrawalRequest->meta, 'payout_beneficiary'),
            ]);

            if ($externalStatus === 'SUCCESS') {
                return $this->completeWithdrawalPayout(
                    withdrawalRequest: $withdrawalRequest,
                    payload: $payload,
                    meta: $meta,
                    externalStatus: $externalStatus,
                );
            }

            if (in_array($externalStatus, ['PROCESSING', 'PENDING'], true)) {
                $withdrawalRequest->forceFill([
                    'status' => 'processing',
                    'paid_at' => null,
                    'failure_reason' => null,
                    'meta' => $meta,
                ])->save();

                return [
                    'updated' => true,
                    'request' => $withdrawalRequest->fresh(),
                    'external_status' => $externalStatus,
                ];
            }

            $withdrawalRequest->forceFill([
                'status' => 'failed',
                'paid_at' => null,
                'failure_reason' => $externalStatus !== '' ? 'ClickPesa payout returned '.$externalStatus.'.' : 'ClickPesa payout did not return a successful status.',
                'meta' => $meta,
            ])->save();

            return [
                'updated' => true,
                'request' => $withdrawalRequest->fresh(),
                'external_status' => $externalStatus,
                'error' => $withdrawalRequest->failure_reason,
            ];
        });
    }

    private function completeWithdrawalPayout(WithdrawalRequest $withdrawalRequest, array $payload, array $meta, string $externalStatus): array
    {
        $wallet = WorkspaceWallet::query()
            ->whereKey($withdrawalRequest->workspace_wallet_id)
            ->lockForUpdate()
            ->firstOrFail();

        $amount = (float) $withdrawalRequest->amount;
        $availableBalance = (float) $wallet->available_balance;
        $pendingBefore = (float) $wallet->pending_withdrawal_balance;
        $pendingAfter = max(0, $pendingBefore - $amount);

        $wallet->forceFill([
            'pending_withdrawal_balance' => $pendingAfter,
            'lifetime_withdrawn' => (float) $wallet->lifetime_withdrawn + $amount,
        ])->save();

        $withdrawalRequest->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
            'failure_reason' => null,
            'meta' => $meta,
        ])->save();

        $transaction = WalletTransaction::create([
            'workspace_wallet_id' => $wallet->id,
            'workspace_id' => $withdrawalRequest->workspace_id,
            'type' => 'withdrawal_paid',
            'amount' => $amount,
            'currency' => $wallet->currency,
            'balance_before' => $availableBalance,
            'balance_after' => $availableBalance,
            'reference_type' => WithdrawalRequest::class,
            'reference_id' => $withdrawalRequest->id,
            'external_reference' => (string) ($payload['id'] ?? $withdrawalRequest->reference),
            'description' => 'Withdrawal payout completed',
            'meta' => [
                'external_status' => $externalStatus,
                'pending_before' => $pendingBefore,
                'pending_after' => $pendingAfter,
            ],
        ]);

        return [
            'updated' => true,
            'request' => $withdrawalRequest->fresh(),
            'wallet' => $wallet->fresh(),
            'transaction' => $transaction,
            'external_status' => $externalStatus,
        ];
    }
}
