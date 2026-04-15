<?php

namespace App\Models;

use Database\Factories\OmadaSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'controller_url', 'username', 'password', 'api_key',
    'hotspot_operator_name', 'hotspot_operator_password',
    'external_portal_url', 'site_id', 'omada_id',
    'is_connected', 'last_synced_at',
])]
class OmadaSetting extends Model
{
    /** @use HasFactory<OmadaSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'password' => 'encrypted',
            'api_key' => 'encrypted',
            'hotspot_operator_password' => 'encrypted',
            'is_connected' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the single settings row (singleton pattern).
     */
    public static function instance(): static
    {
        return static::first() ?? new static;
    }
}
