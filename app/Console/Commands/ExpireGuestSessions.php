<?php

namespace App\Console\Commands;

use App\Events\SessionDisconnected;
use App\Models\GuestSession;
use App\Services\OmadaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('sessions:expire')]
#[Description('Expire guest sessions whose time or data limit has been reached and disconnect them from Omada')]
class ExpireGuestSessions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = $this->expireByTime();
        $dataExpired = $this->expireByData();

        $total = $expired + $dataExpired;

        if ($total > 0) {
            $this->info("Expired {$total} session(s): {$expired} by time, {$dataExpired} by data limit.");
        } else {
            $this->info('No sessions to expire.');
        }

        return self::SUCCESS;
    }

    /**
     * Expire sessions whose time has elapsed.
     */
    private function expireByTime(): int
    {
        $sessions = GuestSession::query()
            ->with('workspace')
            ->where('status', 'active')
            ->whereNotNull('time_expires')
            ->where('time_expires', '<=', now())
            ->get();

        foreach ($sessions as $session) {
            $this->disconnectSession($session, 'time_expired');
        }

        return $sessions->count();
    }

    /**
     * Expire sessions whose data limit has been reached.
     */
    private function expireByData(): int
    {
        $sessions = GuestSession::query()
            ->with('workspace')
            ->where('status', 'active')
            ->whereNotNull('data_limit_mb')
            ->where('data_limit_mb', '>', 0)
            ->whereColumn('data_used_mb', '>=', 'data_limit_mb')
            ->get();

        foreach ($sessions as $session) {
            $this->disconnectSession($session, 'data_exhausted');
        }

        return $sessions->count();
    }

    /**
     * Mark session as expired and unauthorize on Omada.
     */
    private function disconnectSession(GuestSession $session, string $reason): void
    {
        $session->update([
            'status' => 'expired',
            'time_ended' => now(),
        ]);

        SessionDisconnected::dispatch($session);

        // Unauthorize on Omada controller
        if ($session->client_mac && $session->client_mac !== 'unknown') {
            try {
                $omada = app(OmadaService::class);

                if ($omada->isConfigured() && $session->workspace) {
                    $result = $omada->unauthorizeClient($session->client_mac, $session->workspace);

                    if ($result['success']) {
                        Log::channel('omada')->info("Session expired ({$reason}): disconnected {$session->client_mac}");
                    } else {
                        Log::channel('omada')->warning("Session expired ({$reason}): failed to disconnect {$session->client_mac}", [
                            'error' => $result['error'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::channel('omada')->error("Session expire disconnect error: {$e->getMessage()}", [
                    'session_id' => $session->id,
                ]);
            }
        }
    }
}
