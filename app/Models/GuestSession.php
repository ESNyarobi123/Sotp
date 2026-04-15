<?php

namespace App\Models;

use Database\Factories\GuestSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_mac', 'ap_mac', 'ip_address', 'ssid', 'username',
    'plan_id', 'data_used_mb', 'data_limit_mb',
    'time_started', 'time_expires', 'time_ended',
    'status', 'omada_auth_id',
])]
class GuestSession extends Model
{
    /** @use HasFactory<GuestSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_used_mb' => 'decimal:2',
            'data_limit_mb' => 'decimal:2',
            'time_started' => 'datetime',
            'time_expires' => 'datetime',
            'time_ended' => 'datetime',
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
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope: active sessions only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if this session is still active (not expired by time or data).
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->time_expires && $this->time_expires->isPast()) {
            return false;
        }

        if ($this->data_limit_mb && $this->data_used_mb >= $this->data_limit_mb) {
            return false;
        }

        return true;
    }

    /**
     * Get remaining time as a human-readable string.
     */
    public function timeRemaining(): ?string
    {
        if (! $this->time_expires) {
            return null;
        }

        if ($this->time_expires->isPast()) {
            return 'Expired';
        }

        return $this->time_expires->diffForHumans(syntax: true);
    }

    /**
     * Get remaining data in MB.
     */
    public function dataRemaining(): ?float
    {
        if (! $this->data_limit_mb) {
            return null;
        }

        return max(0, $this->data_limit_mb - $this->data_used_mb);
    }
}
