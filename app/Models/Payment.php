<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id', 'phone_number', 'amount', 'currency',
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
     * Scope: completed payments only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: pending payments only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
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
        return number_format($this->amount, 0) . ' ' . $this->currency;
    }
}
