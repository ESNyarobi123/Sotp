<?php

namespace App\Models;

use Database\Factories\WorkspaceWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'currency',
    'available_balance',
    'pending_withdrawal_balance',
    'lifetime_credited',
    'lifetime_withdrawn',
])]
class WorkspaceWallet extends Model
{
    /** @use HasFactory<WorkspaceWalletFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_withdrawal_balance' => 'decimal:2',
            'lifetime_credited' => 'decimal:2',
            'lifetime_withdrawn' => 'decimal:2',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
