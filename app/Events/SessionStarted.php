<?php

namespace App\Events;

use App\Models\GuestSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GuestSession $session,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'SessionStarted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'client_mac' => $this->session->client_mac,
            'plan' => $this->session->plan?->name,
            'expires' => $this->session->time_expires?->toIso8601String(),
        ];
    }
}
