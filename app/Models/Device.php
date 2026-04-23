<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'name', 'ap_mac', 'omada_device_id', 'site_name', 'ip_address',
    'model', 'firmware_version', 'clients_count', 'uptime_seconds',
    'channel_2g', 'channel_5g', 'tx_power_2g', 'tx_power_5g',
    'status', 'last_seen_at',
])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clients_count' => 'integer',
            'uptime_seconds' => 'integer',
            'tx_power_2g' => 'integer',
            'tx_power_5g' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get human-readable uptime string.
     */
    public function uptimeForHumans(): string
    {
        $seconds = $this->uptime_seconds;

        if ($seconds < 60) {
            return "{$seconds}s";
        }
        if ($seconds < 3600) {
            return floor($seconds / 60).'m';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m';
        }

        return floor($seconds / 86400).'d '.floor(($seconds % 86400) / 3600).'h';
    }

    /**
     * Scope: online devices only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    /**
     * Check if device is currently online.
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
