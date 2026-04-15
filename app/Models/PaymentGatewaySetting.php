<?php

namespace App\Models;

use Database\Factories\PaymentGatewaySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'gateway', 'display_name', 'is_active', 'config',
])]
class PaymentGatewaySetting extends Model
{
    /** @use HasFactory<PaymentGatewaySettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'encrypted:array',
        ];
    }

    /**
     * Scope: active gateways only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get a config value by key.
     */
    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}
