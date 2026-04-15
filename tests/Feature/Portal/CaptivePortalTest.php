<?php

use App\Livewire\Portal\CaptivePortal;
use App\Models\GuestSession;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    PaymentGatewaySetting::create([
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'TEST', 'api_key' => 'key'],
    ]);
});

test('portal page renders with plans', function () {
    Plan::factory()->create(['name' => 'Basic WiFi', 'is_active' => true, 'price' => 500]);
    Plan::factory()->create(['name' => 'Premium', 'is_active' => true, 'price' => 2000]);

    Livewire::test(CaptivePortal::class)
        ->assertSee('Choose Your Plan')
        ->assertSee('Basic WiFi')
        ->assertSee('Premium');
});

test('portal does not show inactive plans', function () {
    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Hidden Plan', 'is_active' => false]);

    Livewire::test(CaptivePortal::class)
        ->assertSee('Active Plan')
        ->assertDontSee('Hidden Plan');
});

test('selecting a plan moves to phone entry', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'enter_phone')
        ->assertSet('selectedPlanId', $plan->id);
});

test('free plan skips payment and goes to success', function () {
    $plan = Plan::factory()->create([
        'is_active' => true,
        'price' => 0,
        'type' => 'time',
        'value' => 30,
    ]);

    Livewire::test(CaptivePortal::class, ['clientMac' => 'AA:BB:CC:DD:EE:FF'])
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'success');

    $this->assertDatabaseHas('guest_sessions', [
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);
});

test('back to plans resets state', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'enter_phone')
        ->call('backToPlans')
        ->assertSet('step', 'select_plan')
        ->assertSet('selectedPlanId', null);
});

test('phone number validation works', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '123')
        ->call('initiatePayment')
        ->assertHasErrors(['phoneNumber']);
});

test('initiating payment creates pending record and moves to processing', function () {
    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP123',
            'status' => 'PROCESSING',
            'orderReference' => 'SKY_TEST',
        ]),
    ]);

    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    $component = Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '255712345678')
        ->call('initiatePayment')
        ->assertSet('step', 'processing');

    $transactionId = $component->get('transactionId');
    $this->assertDatabaseHas('payments', [
        'transaction_id' => $transactionId,
        'phone_number' => '255712345678',
        'status' => 'pending',
        'plan_id' => $plan->id,
    ]);
});

test('check payment status transitions to success on completed', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP123',
            'status' => 'PROCESSING',
        ]),
    ]);

    $component = Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '255712345678')
        ->call('initiatePayment');

    $txId = $component->get('transactionId');

    // Simulate webhook completing the payment
    \App\Models\Payment::where('transaction_id', $txId)->update(['status' => 'completed', 'paid_at' => now()]);

    $component->call('checkPaymentStatus')
        ->assertSet('step', 'success');
});

test('check payment status transitions to error on failed', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP123',
            'status' => 'PROCESSING',
        ]),
    ]);

    $component = Livewire::test(CaptivePortal::class)
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '255712345678')
        ->call('initiatePayment');

    $txId = $component->get('transactionId');

    \App\Models\Payment::where('transaction_id', $txId)->update(['status' => 'failed']);

    $component->call('checkPaymentStatus')
        ->assertSet('step', 'error');
});

test('retry goes back to phone entry', function () {
    $plan = Plan::factory()->create(['is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class)
        ->set('step', 'error')
        ->set('selectedPlanId', $plan->id)
        ->call('retry')
        ->assertSet('step', 'enter_phone')
        ->assertSet('errorMessage', null);
});

test('existing active session shows success directly', function () {
    $session = GuestSession::factory()->active()->create([
        'client_mac' => 'AA:BB:CC:DD:EE:FF',
    ]);

    Livewire::withQueryParams(['clientMac' => 'AA:BB:CC:DD:EE:FF'])
        ->test(CaptivePortal::class)
        ->assertSet('step', 'success');
});

test('portal is accessible without authentication', function () {
    Plan::factory()->create(['is_active' => true]);

    $response = $this->get('/portal');

    $response->assertOk();
});
