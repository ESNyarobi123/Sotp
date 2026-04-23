<?php

use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\Workspace;

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

test('successful payment creates guest session', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'type' => 'time', 'value' => 60]);

    $payment = Payment::factory()->create([
        'workspace_id' => $this->workspace->id,
        'status' => 'pending',
        'transaction_id' => 'PROV_TEST_1',
        'plan_id' => $plan->id,
        'client_mac' => 'AA:BB:CC:DD:EE:FF',
        'ap_mac' => '11:22:33:44:55:66',
        'phone_number' => '255712345678',
        'metadata' => ['ssid' => 'SKY-WiFi', 'ip_address' => '10.0.0.5'],
    ]);

    $response = $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'id' => 'PROV_TEST_1_CP',
            'status' => 'SUCCESS',
            'orderReference' => 'PROV_TEST_1',
            'collectedAmount' => '1000',
            'channel' => 'M-PESA',
        ],
    ]);

    $response->assertOk();

    expect($payment->fresh()->status)->toBe('completed');

    $this->assertDatabaseHas('guest_sessions', [
        'workspace_id' => $this->workspace->id,
        'client_mac' => 'AA:BB:CC:DD:EE:FF',
        'plan_id' => $plan->id,
        'status' => 'active',
        'username' => '255712345678',
        'ssid' => 'SKY-WiFi',
    ]);

    expect($payment->fresh()->guest_session_id)->not->toBeNull();
});

test('data plan creates session with data limit', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'type' => 'data', 'value' => 500]);

    $payment = Payment::factory()->create([
        'workspace_id' => $this->workspace->id,
        'status' => 'pending',
        'transaction_id' => 'PROV_DATA_1',
        'plan_id' => $plan->id,
        'client_mac' => 'BB:CC:DD:EE:FF:00',
    ]);

    $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT RECEIVED',
        'data' => [
            'id' => 'PROV_DATA_1_CP',
            'status' => 'SUCCESS',
            'orderReference' => 'PROV_DATA_1',
            'collectedAmount' => '2000',
            'channel' => 'TIGO-PESA',
        ],
    ]);

    $this->assertDatabaseHas('guest_sessions', [
        'workspace_id' => $this->workspace->id,
        'client_mac' => 'BB:CC:DD:EE:FF:00',
        'data_limit_mb' => 500,
        'status' => 'active',
    ]);
});

test('failed payment does not create guest session', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id]);

    Payment::factory()->create([
        'workspace_id' => $this->workspace->id,
        'status' => 'pending',
        'transaction_id' => 'PROV_FAIL_1',
        'plan_id' => $plan->id,
        'client_mac' => 'CC:DD:EE:FF:00:11',
    ]);

    $this->postJson('/api/clickpesa/webhook', [
        'event' => 'PAYMENT FAILED',
        'data' => [
            'id' => 'PROV_FAIL_1_CP',
            'orderReference' => 'PROV_FAIL_1',
            'message' => 'Insufficient balance',
        ],
    ]);

    $this->assertDatabaseMissing('guest_sessions', [
        'client_mac' => 'CC:DD:EE:FF:00:11',
    ]);
});
