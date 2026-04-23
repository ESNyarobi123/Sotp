<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'transaction_id', 'phone_number', 'amount', 'currency',
    'payment_method', 'status', 'plan_id', 'guest_session_id',
    'client_mac', 'ap_mac', 'mpesa_checkout_request_id',
    'mpesa_receipt_number', 'clickpesa_order_id',
    'clickpesa_payment_reference', 'clickpesa_channel',
    'metadata', 'paid_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if ($payment->workspace_id === null && $payment->plan_id) {
                $payment->workspace_id = Plan::query()->whereKey($payment->plan_id)->value('workspace_id');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<GuestSession, $this>
     */
    public function guestSession(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope: completed payments only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: pending payments only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get formatted amount with currency.
     */
    public function formattedAmount(): string
    {
        return number_format($this->amount, 0).' '.$this->currency;
    }
}
