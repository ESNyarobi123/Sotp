<?php

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\OmadaService;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProvisionWorkspaceOmadaSiteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Workspace $workspace) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(OmadaService $omada): void
    {
        $this->workspace->refresh();

        if ($this->workspace->omada_site_id) {
            $this->workspace->forceFill([
                'provisioning_status' => 'ready',
                'provisioning_error' => null,
                'provisioning_next_retry_at' => null,
            ])->save();

            return;
        }

        $attemptNumber = ((int) $this->workspace->provisioning_attempts) + 1;

        $this->workspace->forceFill([
            'provisioning_status' => 'provisioning',
            'provisioning_error' => null,
            'provisioning_attempts' => $attemptNumber,
            'provisioning_last_attempted_at' => now(),
            'provisioning_next_retry_at' => null,
        ])->save();

        if (! $omada->isConfigured()) {
            $this->workspace->forceFill([
                'provisioning_status' => 'failed',
                'provisioning_error' => 'Omada Open API is not configured on the server (missing OMADA_* environment variables).',
                'provisioning_next_retry_at' => null,
            ])->save();

            return;
        }

        $result = $omada->createSiteForBrand($this->workspace->brand_name);

        if (! $result['success'] || empty($result['siteId'])) {
            if (($result['retryable'] ?? false) === true) {
                $this->workspace->forceFill([
                    'provisioning_next_retry_at' => $this->nextRetryAt($attemptNumber),
                ])->save();

                Log::warning('ProvisionWorkspaceOmadaSiteJob retry scheduled', [
                    'workspace_id' => $this->workspace->id,
                    'error' => $result['error'] ?? null,
                    'error_code' => $result['error_code'] ?? null,
                ]);

                throw new RuntimeException($result['error'] ?? 'Retryable Omada provisioning error');
            }

            $this->workspace->forceFill([
                'provisioning_status' => 'failed',
                'provisioning_error' => $result['error'] ?? 'Unknown error creating Omada site',
                'provisioning_next_retry_at' => null,
            ])->save();

            Log::warning('ProvisionWorkspaceOmadaSiteJob failed', [
                'workspace_id' => $this->workspace->id,
                'error' => $result['error'] ?? null,
                'error_code' => $result['error_code'] ?? null,
            ]);

            return;
        }

        $this->workspace->forceFill([
            'omada_site_id' => $result['siteId'],
            'provisioning_status' => 'ready',
            'provisioning_error' => null,
            'provisioning_next_retry_at' => null,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->workspace->refresh();

        if ($this->workspace->omada_site_id) {
            $this->workspace->forceFill([
                'provisioning_status' => 'ready',
                'provisioning_error' => null,
                'provisioning_next_retry_at' => null,
            ])->save();

            return;
        }

        $this->workspace->forceFill([
            'provisioning_status' => 'failed',
            'provisioning_error' => $exception?->getMessage() ?: 'Omada provisioning job failed before completing.',
            'provisioning_next_retry_at' => null,
        ])->save();

        Log::warning('ProvisionWorkspaceOmadaSiteJob marked workspace as failed', [
            'workspace_id' => $this->workspace->id,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function nextRetryAt(int $attemptNumber): ?DateTimeInterface
    {
        if ($attemptNumber >= $this->tries) {
            return null;
        }

        $backoff = $this->backoff();
        $delaySeconds = $backoff[min($attemptNumber - 1, count($backoff) - 1)] ?? null;

        return $delaySeconds !== null ? now()->addSeconds($delaySeconds) : null;
    }
}
