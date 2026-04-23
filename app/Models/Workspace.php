<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'brand_name', 'public_slug', 'omada_site_id',
    'provisioning_status', 'provisioning_error', 'provisioning_attempts',
    'provisioning_last_attempted_at', 'provisioning_next_retry_at', 'devices_last_synced_at',
    'max_devices', 'max_plans', 'max_sessions',
    'is_suspended', 'suspension_reason', 'suspended_at',
])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provisioning_attempts' => 'integer',
            'provisioning_last_attempted_at' => 'datetime',
            'provisioning_next_retry_at' => 'datetime',
            'devices_last_synced_at' => 'datetime',
            'max_devices' => 'integer',
            'max_plans' => 'integer',
            'max_sessions' => 'integer',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * @return HasMany<Plan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
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
     * @return HasMany<PaymentGatewaySetting, $this>
     */
    public function paymentGatewaySettings(): HasMany
    {
        return $this->hasMany(PaymentGatewaySetting::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(WorkspaceWallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function availableWalletBalance(): string
    {
        if (! Schema::hasTable('workspace_wallets')) {
            return '0.00';
        }

        return (string) ($this->wallet()->value('available_balance') ?? '0.00');
    }

    public function isOmadaReady(): bool
    {
        return $this->provisioning_status === 'ready' && $this->omada_site_id !== null && $this->omada_site_id !== '';
    }

    /**
     * @return array{category: string, title: string, message: string, retryable: bool}|null
     */
    public function provisioningErrorSummary(): ?array
    {
        if ($this->provisioning_status !== 'failed' || ! filled($this->provisioning_error)) {
            return null;
        }

        $error = strtolower((string) $this->provisioning_error);

        if (str_contains($error, 'not configured') || str_contains($error, 'missing omada_')) {
            return [
                'category' => 'configuration',
                'title' => 'Open API configuration is incomplete',
                'message' => 'Complete the controller URL, Open API credentials, and controller ID readiness checks before retrying provisioning.',
                'retryable' => false,
            ];
        }

        if (str_contains($error, 'authenticate') || str_contains($error, 'authentication') || str_contains($error, '401') || str_contains($error, '403')) {
            return [
                'category' => 'authentication',
                'title' => 'Controller authentication failed',
                'message' => 'Re-check the controller credentials, test the connection again, then retry provisioning.',
                'retryable' => false,
            ];
        }

        if (str_contains($error, 'temporarily unavailable') || str_contains($error, 'timeout') || str_contains($error, 'connection refused') || str_contains($error, 'controller unavailable')) {
            return [
                'category' => 'controller_unavailable',
                'title' => 'Controller is temporarily unavailable',
                'message' => 'Wait for the controller to recover, then retry provisioning from the Omada Integration page.',
                'retryable' => true,
            ];
        }

        if (str_contains($error, 'rate limit') || str_contains($error, 'too many')) {
            return [
                'category' => 'rate_limited',
                'title' => 'Controller rate limit reached',
                'message' => 'Give the controller a moment, then retry provisioning after the rate limit window resets.',
                'retryable' => true,
            ];
        }

        return [
            'category' => 'unknown',
            'title' => 'Provisioning failed with an unknown controller response',
            'message' => 'Review the error details below, confirm the readiness checks, then retry provisioning.',
            'retryable' => false,
        ];
    }

    /**
     * @return array{status: string, badge_color: string, callout_variant: string, icon: string, title: string, message: string, action_label: string|null}
     */
    public function provisioningSummary(): array
    {
        if ($this->isOmadaReady()) {
            return [
                'status' => 'ready',
                'badge_color' => 'emerald',
                'callout_variant' => 'success',
                'icon' => 'check-circle',
                'title' => 'Omada site is ready',
                'message' => 'This workspace is connected to its dedicated Omada site and networking actions can run normally.',
                'action_label' => null,
            ];
        }

        if ($this->provisioning_status === 'failed') {
            $errorSummary = $this->provisioningErrorSummary();

            return [
                'status' => 'failed',
                'badge_color' => 'red',
                'callout_variant' => 'danger',
                'icon' => 'x-circle',
                'title' => $errorSummary['title'] ?? 'Omada provisioning needs attention',
                'message' => $errorSummary['message'] ?? 'The workspace site was not created successfully. Review readiness issues, then retry provisioning from the Omada Integration page.',
                'action_label' => ($errorSummary['retryable'] ?? false) ? 'Retry provisioning' : 'Review readiness and retry',
            ];
        }

        if ($this->provisioning_status === 'provisioning') {
            return [
                'status' => 'provisioning',
                'badge_color' => 'amber',
                'callout_variant' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'Omada site provisioning is in progress',
                'message' => 'We are creating your dedicated Omada site in the background. Device sync and captive portal will work once this finishes.',
                'action_label' => null,
            ];
        }

        return [
            'status' => 'pending',
            'badge_color' => 'amber',
            'callout_variant' => 'warning',
            'icon' => 'clock',
            'title' => 'WiFi location is queued for setup',
            'message' => 'This workspace is waiting for the provisioning job to run. Once the site is created, Omada-dependent actions will become ready.',
            'action_label' => null,
        ];
    }

    /**
     * @return array{attempts: int, last_attempted_at: string|null, last_attempted_human: string|null, next_retry_at: string|null, next_retry_human: string|null}
     */
    public function provisioningLifecycleSummary(): array
    {
        return [
            'attempts' => (int) ($this->provisioning_attempts ?? 0),
            'last_attempted_at' => $this->provisioning_last_attempted_at?->toIso8601String(),
            'last_attempted_human' => $this->provisioning_last_attempted_at?->diffForHumans(),
            'next_retry_at' => $this->provisioning_next_retry_at?->toIso8601String(),
            'next_retry_human' => $this->provisioning_next_retry_at?->diffForHumans(),
        ];
    }

    public function portalUrl(): string
    {
        return url('/portal/'.$this->public_slug);
    }

    public function getRouteKeyName(): string
    {
        return 'public_slug';
    }

    public static function uniquePublicSlugFromBrand(string $brandName): string
    {
        $base = Str::slug($brandName);

        if ($base === '') {
            $base = 'wifi';
        }

        $slug = $base.'-'.Str::lower(Str::random(4));

        while (static::where('public_slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
