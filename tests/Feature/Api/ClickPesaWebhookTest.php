<?php

use App\Models\Payment;
use App\Models\PaymentGatewaySetting;

beforeEach(function () {
    PaymentGatewaySetting::create([
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);
});

test('webhook handles PAYMENT RECEIVED event', function () {
    $payment = Payment::factory()->create([
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
});

test('webhook handles PAYMENT FAILED event', function () {
    $payment = Payment::factory()->create([
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

test('webhook ignores already completed payment', function () {
    $payment = Payment::factory()->completed()->create([
        'transaction_id' => 'ORD_DONE',
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
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'transaction_id' => 'CH_' . $channel,
            'paid_at' => null,
        ]);

        $this->postJson('/api/clickpesa/webhook', [
            'event' => 'PAYMENT RECEIVED',
            'data' => [
                'id' => 'ID_' . $channel,
                'orderReference' => 'CH_' . $channel,
                'status' => 'SUCCESS',
                'channel' => $channel,
                'collectedAmount' => '1000',
            ],
        ]);

        expect($payment->fresh()->payment_method)->toBe($expectedMethod, "Channel {$channel} should map to {$expectedMethod}");
    }
});
