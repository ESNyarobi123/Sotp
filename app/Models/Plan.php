<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'name', 'type', 'value', 'duration_minutes',
    'price', 'validity_days', 'description', 'is_active', 'sort_order',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'value' => 'integer',
            'duration_minutes' => 'integer',
            'validity_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<GuestSession, $this>
     */
    public function guestSessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope: only active plans.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get human-readable value display.
     */
    public function formattedValue(): string
    {
        return match ($this->type) {
            'time' => $this->value !== null
                ? ($this->value >= 60 ? round($this->value / 60, 1).' hours' : $this->value.' min')
                : '— min',
            'data' => $this->value !== null
                ? ($this->value >= 1024 ? round($this->value / 1024, 1).' GB' : $this->value.' MB')
                : '— MB',
            'unlimited' => $this->duration_minutes
                ? round($this->duration_minutes / 60, 1).' hours unlimited'
                : 'Unlimited',
            default => '—',
        };
    }
}
