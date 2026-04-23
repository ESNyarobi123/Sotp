<?php

use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\WalletTransaction;
use App\Models\Workspace;
use App\Models\WorkspaceWallet;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();

    PaymentGatewaySetting::create([
        'workspace_id' => null,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);
});

function makePaymentForWebhook(Workspace $workspace, array $overrides = []): Payment
{
    $plan = Plan::factory()->create(['workspace_id' => $workspace->id]);

    return Payment::factory()->create(array_merge([
        'workspace_id' => $workspace->id,
        'plan_id' => $plan->id,
    ], $overrides));
}

test('webhook handles PAYMENT RECEIVED event', function () {
    $payment = makePaymentForWebhook($this->workspace, [
        'amount' => 1000,
        'status' => 'pending',
        'transaction_id' => 'ORD123456',
        'paid_at' => null,
    ]);

    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'id' => 'ORD123456LCP7890',
            'status' => 'SUCCESS',
            'paymentReference' => 'abc123def456',
            'orderReference' => 'ORD123456',
            'collectedAmount' => '1000',
            'collectedCurrency' => 'TZS',
            'channel' => 'M-PESA',
            'message' => 'success',
            'customer' => [
                'customerName' => 'John Doe',
                'customerPhoneNumber' => '255712345678',
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Payment processed successfully']);

    $payment->refresh();
    expect($payment->status)->toBe('completed');
    expect($payment->paid_at)->not->toBeNull();
    expect($payment->clickpesa_order_id)->toBe('ORD123456LCP7890');
    expect($payment->clickpesa_payment_reference)->toBe('abc123def456');
    expect($payment->clickpesa_channel)->toBe('M-PESA');
    expect($payment->payment_method)->toBe('mpesa');

    $wallet = WorkspaceWallet::where('workspace_id', $this->workspace->id)->first();

    expect($wallet)->not->toBeNull();
    expect((float) $wallet->available_balance)->toBe(1000.0);
    expect((float) $wallet->lifetime_credited)->toBe(1000.0);

    $transaction = WalletTransaction::where('workspace_id', $this->workspace->id)
        ->where('type', 'payment_credit')
        ->where('reference_id', $payment->id)
        ->first();

    expect($transaction)->not->toBeNull();
    expect((float) $transaction->amount)->toBe(1000.0);
    expect((float) $transaction->balance_after)->toBe(1000.0);
});

test('webhook handles PAYMENT FAILED event', function () {
    $payment = makePaymentForWebhook($this->workspace, [
        'status' => 'pending',
        'transaction_id' => 'ORD789',
        'paid_at' => null,
    ]);

    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT FAILED',
        'data' => [
            'id' => 'ORD789LCP111',
            'status' => 'FAILED',
            'channel' => 'TIGO-PESA',
            'orderReference' => 'ORD789',
            'message' => 'Insufficient balance',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Failure recorded']);

    $payment->refresh();
    expect($payment->status)->toBe('failed');
    expect($payment->clickpesa_order_id)->toBe('ORD789LCP111');
});

test('duplicate PAYMENT RECEIVED webhook does not double credit wallet', function () {
    $payment = makePaymentForWebhook($this->workspace, [
        'amount' => 1000,
        'status' => 'pending',
        'transaction_id' => 'ORD_DUPLICATE',
        'paid_at' => null,
    ]);

    $payload = [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'id' => 'ORD_DUPLICATE_CP',
            'status' => 'SUCCESS',
            'paymentReference' => 'duplicate-ref',
            'orderReference' => 'ORD_DUPLICATE',
            'collectedAmount' => '1000',
            'collectedCurrency' => 'TZS',
            'channel' => 'M-PESA',
        ],
    ];

    $this->postJson('/api/clickpesa/webhook', $payload)->assertOk();
    $this->postJson('/api/clickpesa/webhook', $payload)->assertOk();

    $wallet = WorkspaceWallet::where('workspace_id', $this->workspace->id)->first();

    expect($wallet)->not->toBeNull();
    expect((float) $wallet->available_balance)->toBe(1000.0);
    expect(WalletTransaction::where('workspace_id', $this->workspace->id)->where('type', 'payment_credit')->count())->toBe(1);
});

test('webhook ignores already completed payment', function () {
    $payment = makePaymentForWebhook($this->workspace, [
        'status' => 'completed',
        'transaction_id' => 'ORD_DONE',
        'paid_at' => now(),
    ]);

    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'id' => 'ORD_DONELCP',
            'orderReference' => 'ORD_DONE',
            'status' => 'SUCCESS',
            'collectedAmount' => '1000',
            'channel' => 'M-PESA',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Already processed']);
});

test('webhook returns 404 for unknown order reference', function () {
    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'orderReference' => 'UNKNOWN_REF',
            'status' => 'SUCCESS',
        ],
    ]);

    $response->assertNotFound();
});

test('webhook ignores unhandled events', function () {
    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYOUT INITIATED',
        'data' => [],
    ]);

    $response->assertOk();
    $response->assertJson(['message' => 'Event ignored']);
});

test('webhook maps channels correctly', function () {
    $channels = [
        'M-PESA' => 'mpesa',
        'TIGO-PESA' => 'tigo',
        'AIRTEL-MONEY' => 'airtel',
        'HALOPESA' => 'halopesa',
        'MIXX BY YAS' => 'tigo',
    ];

    foreach ($channels as $channel => $expectedMethod) {
        $payment = makePaymentForWebhook($this->workspace, [
            'status' => 'pending',
            'transaction_id' => 'CH_'.$channel,
            'paid_at' => null,
        ]);

        $this->postJson('/api/clickpesa/webhook', [
            'event' => 'PAYMENT RECEIVED',
            'data' => [
                'id' => 'ID_'.$channel,
                'orderReference' => 'CH_'.$channel,
                'status' => 'SUCCESS',
                'channel' => $channel,
                'collectedAmount' => '1000',
            ],
        ]);

        expect($payment->fresh()->payment_method)->toBe($expectedMethod, "Channel {$channel} should map to {$expectedMethod}");
    }
});
