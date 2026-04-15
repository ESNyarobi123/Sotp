<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentCompleted as PaymentCompletedEvent;
use App\Events\SessionStarted;
use App\Http\Controllers\Controller;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Services\ClickPesaService;
use App\Services\OmadaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClickPesaWebhookController extends Controller
{
    public function __construct(
        private ClickPesaService $clickPesa,
    ) {}

    /**
     * Handle incoming ClickPesa webhook events.
     *
     * Events: PAYMENT RECEIVED, PAYMENT FAILED
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('ClickPesa webhook received', ['event' => $event, 'orderReference' => $data['orderReference'] ?? null]);

        // Validate checksum if present
        if (isset($payload['checksum'])) {
            if (! $this->clickPesa->validateWebhookChecksum($payload, $payload['checksum'])) {
                Log::warning('ClickPesa webhook checksum validation failed', ['event' => $event]);

                return response()->json(['error' => 'Invalid checksum'], 403);
            }
        }

        return match ($event) {
            'PAYMENT RECEIVED' => $this->handlePaymentReceived($data),
            'PAYMENT FAILED' => $this->handlePaymentFailed($data),
            default => response()->json(['message' => 'Event ignored']),
        };
    }

    /**
     * Handle successful payment.
     *
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentReceived(array $data): JsonResponse
    {
        $orderReference = $data['orderReference'] ?? null;

        if (! $orderReference) {
            return response()->json(['error' => 'Missing orderReference'], 400);
        }

        $payment = Payment::where('transaction_id', $orderReference)->first();

        if (! $payment) {
            Log::warning('ClickPesa webhook: payment not found', ['orderReference' => $orderReference]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Already processed']);
        }

        $channel = $data['channel'] ?? '';

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'clickpesa_order_id' => $data['id'] ?? null,
            'clickpesa_payment_reference' => $data['paymentReference'] ?? null,
            'clickpesa_channel' => $channel,
            'payment_method' => ClickPesaService::mapChannelToMethod($channel),
            'metadata' => array_merge($payment->metadata ?? [], [
                'clickpesa_event' => 'PAYMENT RECEIVED',
                'customer' => $data['customer'] ?? null,
                'collected_amount' => $data['collectedAmount'] ?? null,
                'message' => $data['message'] ?? null,
            ]),
        ]);

        Log::info('ClickPesa payment completed', [
            'transaction_id' => $orderReference,
            'channel' => $channel,
            'amount' => $data['collectedAmount'] ?? null,
        ]);

        // Create guest session and authorize on Omada
        $this->provisionAccess($payment);

        // Broadcast real-time event for admin dashboard
        PaymentCompletedEvent::dispatch($payment);

        return response()->json(['message' => 'Payment processed successfully']);
    }

    /**
     * Provision WiFi access: create GuestSession + authorize on Omada.
     */
    private function provisionAccess(Payment $payment): void
    {
        $plan = $payment->plan;

        if (! $plan) {
            Log::warning('Cannot provision access: no plan linked', ['payment_id' => $payment->id]);

            return;
        }

        $durationMinutes = match ($plan->type) {
            'time' => $plan->value,
            'unlimited' => $plan->duration_minutes ?? 60,
            default => 60,
        };

        $metadata = $payment->metadata ?? [];

        $session = GuestSession::create([
            'client_mac' => $payment->client_mac ?? 'unknown',
            'ap_mac' => $payment->ap_mac ?? 'unknown',
            'ip_address' => $metadata['ip_address'] ?? null,
            'ssid' => $metadata['ssid'] ?? null,
            'username' => $payment->phone_number,
            'plan_id' => $plan->id,
            'data_used_mb' => 0,
            'data_limit_mb' => $plan->type === 'data' ? $plan->value : null,
            'time_started' => now(),
            'time_expires' => now()->addMinutes($durationMinutes),
            'status' => 'active',
        ]);

        $payment->update(['guest_session_id' => $session->id]);

        // Broadcast session started event
        SessionStarted::dispatch($session);

        // Authorize client on Omada controller
        if ($payment->client_mac) {
            try {
                $omada = app(OmadaService::class);

                if ($omada->isConfigured()) {
                    $result = $omada->authorizeClient([
                        'clientMac' => $payment->client_mac,
                        'apMac' => $payment->ap_mac ?? '',
                        'ssid' => $metadata['ssid'] ?? '',
                        'minutes' => $durationMinutes,
                    ]);

                    if ($result['success']) {
                        $session->update(['omada_auth_id' => $result['authId']]);
                        Log::info('Omada access granted', ['client_mac' => $payment->client_mac, 'session_id' => $session->id]);
                    } else {
                        Log::warning('Omada authorization failed after payment', ['error' => $result['error'], 'session_id' => $session->id]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Omada authorization exception', ['error' => $e->getMessage(), 'session_id' => $session->id]);
            }
        }
    }

    /**
     * Handle failed payment.
     *
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentFailed(array $data): JsonResponse
    {
        $orderReference = $data['orderReference'] ?? null;

        if (! $orderReference) {
            return response()->json(['error' => 'Missing orderReference'], 400);
        }

        $payment = Payment::where('transaction_id', $orderReference)->first();

        if (! $payment) {
            Log::warning('ClickPesa webhook: payment not found for failure', ['orderReference' => $orderReference]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Already completed, ignoring failure']);
        }

        $payment->update([
            'status' => 'failed',
            'clickpesa_order_id' => $data['id'] ?? null,
            'clickpesa_channel' => $data['channel'] ?? null,
            'metadata' => array_merge($payment->metadata ?? [], [
                'clickpesa_event' => 'PAYMENT FAILED',
                'failure_message' => $data['message'] ?? null,
            ]),
        ]);

        Log::info('ClickPesa payment failed', [
            'transaction_id' => $orderReference,
            'reason' => $data['message'] ?? 'unknown',
        ]);

        return response()->json(['message' => 'Failure recorded']);
    }
}
