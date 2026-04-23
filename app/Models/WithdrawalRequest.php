<?php

namespace App\Models;

use Database\Factories\WithdrawalRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'workspace_wallet_id',
    'requested_by',
    'reviewed_by',
    'reference',
    'status',
    'amount',
    'currency',
    'phone_number',
    'review_notes',
    'failure_reason',
    'approved_at',
    'rejected_at',
    'paid_at',
    'meta',
])]
class WithdrawalRequest extends Model
{
    /** @use HasFactory<WithdrawalRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WorkspaceWallet::class, 'workspace_wallet_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
