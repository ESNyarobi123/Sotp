<?php

use App\Livewire\Portal\CaptivePortal;
use App\Models\GuestSession;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

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

test('portal page renders with plans', function () {
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Basic WiFi', 'is_active' => true, 'price' => 500]);
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Premium', 'is_active' => true, 'price' => 2000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->assertSee('Choose Your Plan')
        ->assertSee('Basic WiFi')
        ->assertSee('Premium');
});

test('portal does not show inactive plans', function () {
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Hidden Plan', 'is_active' => false]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->assertSee('Active Plan')
        ->assertDontSee('Hidden Plan');
});

test('selecting a plan moves to phone entry', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'enter_phone')
        ->assertSet('selectedPlanId', $plan->id);
});

test('free plan skips payment and goes to success', function () {
    $plan = Plan::factory()->create([
        'workspace_id' => $this->workspace->id,
        'is_active' => true,
        'price' => 0,
        'type' => 'time',
        'value' => 30,
    ]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace, 'clientMac' => 'AA:BB:CC:DD:EE:FF'])
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'success');

    $this->assertDatabaseHas('guest_sessions', [
        'plan_id' => $plan->id,
        'workspace_id' => $this->workspace->id,
        'status' => 'active',
    ]);
});

test('back to plans resets state', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->assertSet('step', 'enter_phone')
        ->call('backToPlans')
        ->assertSet('step', 'select_plan')
        ->assertSet('selectedPlanId', null);
});

test('phone number validation works', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
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

    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    $component = Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '712345678')
        ->call('initiatePayment')
        ->assertSet('step', 'processing');

    $transactionId = $component->get('transactionId');
    $this->assertDatabaseHas('payments', [
        'transaction_id' => $transactionId,
        'phone_number' => '255712345678',
        'status' => 'pending',
        'plan_id' => $plan->id,
        'workspace_id' => $this->workspace->id,
    ]);
});

test('workspace-specific clickpesa settings still work when platform settings are missing', function () {
    PaymentGatewaySetting::whereNull('workspace_id')->delete();

    PaymentGatewaySetting::create([
        'workspace_id' => $this->workspace->id,
        'gateway' => 'clickpesa',
        'display_name' => 'ClickPesa',
        'is_active' => true,
        'config' => ['client_id' => 'WORKSPACE_TEST', 'api_key' => 'workspace_key'],
    ]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP124',
            'status' => 'PROCESSING',
            'orderReference' => 'SKY_FALLBACK',
        ]),
    ]);

    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '712345678')
        ->call('initiatePayment')
        ->assertSet('step', 'processing');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/generate-token')
            && $request->hasHeader('client-id', 'WORKSPACE_TEST')
            && $request->hasHeader('api-key', 'workspace_key');
    });
});

test('check payment status transitions to success on completed', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP123',
            'status' => 'PROCESSING',
        ]),
    ]);

    $component = Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '712345678')
        ->call('initiatePayment');

    $txId = $component->get('transactionId');

    Payment::where('transaction_id', $txId)->update(['status' => 'completed', 'paid_at' => now()]);

    $component->call('checkPaymentStatus')
        ->assertSet('step', 'success');
});

test('check payment status transitions to error on failed', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Http::fake([
        '*/generate-token' => Http::response(['success' => true, 'token' => 'Bearer test']),
        '*/initiate-ussd-push-request' => Http::response([
            'id' => 'CP123',
            'status' => 'PROCESSING',
        ]),
    ]);

    $component = Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->call('selectPlan', $plan->id)
        ->set('phoneNumber', '712345678')
        ->call('initiatePayment');

    $txId = $component->get('transactionId');

    Payment::where('transaction_id', $txId)->update(['status' => 'failed']);

    $component->call('checkPaymentStatus')
        ->assertSet('step', 'error');
});

test('retry goes back to phone entry', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true, 'price' => 1000]);

    Livewire::test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->set('step', 'error')
        ->set('selectedPlanId', $plan->id)
        ->call('retry')
        ->assertSet('step', 'enter_phone')
        ->assertSet('errorMessage', null);
});

test('existing active session shows success directly', function () {
    $plan = Plan::factory()->create(['workspace_id' => $this->workspace->id]);

    GuestSession::factory()->active()->create([
        'workspace_id' => $this->workspace->id,
        'plan_id' => $plan->id,
        'client_mac' => 'AA:BB:CC:DD:EE:FF',
    ]);

    Livewire::withQueryParams(['clientMac' => 'AA:BB:CC:DD:EE:FF'])
        ->test(CaptivePortal::class, ['workspace' => $this->workspace])
        ->assertSet('step', 'success');
});

test('portal is accessible without authentication', function () {
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true]);

    $response = $this->get('/portal/'.$this->workspace->public_slug);

    $response->assertOk();
});

test('legacy portal path redirects to first ready workspace', function () {
    Plan::factory()->create(['workspace_id' => $this->workspace->id, 'is_active' => true]);

    $this->get('/portal')
        ->assertRedirect(route('portal.workspace', ['workspace' => $this->workspace->public_slug], absolute: false));
});
