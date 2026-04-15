<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
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
        return 'PaymentCompleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'plan' => $this->payment->plan?->name,
            'method' => $this->payment->payment_method,
            'phone' => $this->payment->phone_number,
        ];
    }
}
